<?php

require_once(__DIR__.'/../../../config.php');
require_login();
global $DB, $USER, $PAGE, $OUTPUT;
$courseid = required_param('courseid', PARAM_INT);
$context = context_course::instance($courseid);
require_capability('local/inveniordm:upload', $context);
$PAGE->requires->css(
    new moodle_url(
        '/local/inveniordm/styles/main.css'
    )
);
$PAGE->requires->css(
    new moodle_url('/local/inveniordm/styles/create_assignment.css')
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $assignment = new stdClass();
    $assignment->courseid = $courseid;
    $assignment->name = required_param('name', PARAM_TEXT);
    $assignment->instructions = optional_param('instructions', '', PARAM_TEXT);
    $assignment->duedate = strtotime(required_param('duedate', PARAM_TEXT));
    $assignment->createdby = $USER->id;
    $assignment->timecreated = time();
    $selectedresources = $_POST['resources'] ?? [];
    $assignmentid = $DB->insert_record('local_inveniordm_assignments', $assignment);
    foreach ($selectedresources as $recordid => $title) {
        $DB->insert_record(
            'local_inveniordm_assignment_resources',
            (object)[
                'assignmentid' => $assignmentid,
                'recordid' => $recordid,
                'title' => $title
            ]
        );
    }
    redirect(
        new moodle_url(
            '/local/inveniordm/lecturer/assignments.php',
            ['courseid' => $courseid]
        ),
        'Assignment created successfully'
    );
}

$PAGE->set_url(
    new moodle_url(
        '/local/inveniordm/lecturer/create_assignment.php',
        ['courseid' => $courseid]
    )
);
$PAGE->set_context($context);
$PAGE->set_title('Create Assignment');

echo $OUTPUT->header();

$backurl = new moodle_url(
    '/local/inveniordm/lecturer/assignments.php',
    ['courseid' => $courseid]
);

$client = new \local_inveniordm\api\invenio_client();
$searchresource = optional_param('searchresource', '', PARAM_TEXT);
$page = optional_param('page', 1, PARAM_INT);
$pagesize = 25;
$records = $client->get_records($searchresource, ['page' => $page, 'size' => $pagesize]);
$hits = $records['hits']['hits'] ?? [];
$totalrecords = $records['hits']['total'] ?? count($hits);
$totalpages = max(1, ceil($totalrecords / $pagesize));

echo '
    <div class="container mt-4">
        <div class="page-header mb-4">
            <div class="page-header-content">
                <h1>
                    <i class="fa fa-plus-circle"></i> 
                    Create Assignment
                </h1>
                <p>Create a new assignment and attach learning resources from the repository.</p>
            </div>
            <div class="page-header-actions">
                <a href="'.$backurl.'" class="btn btn-outline-secondary">
                    <i class="fa fa-arrow-left"></i> 
                    Back to Assignments
                </a>
            </div>
        </div>
';

echo '
    <form method="post" class="assignment-form">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card form-card">
                    <div class="card-header">
                        <h5>
                            <i class="fa fa-info-circle"></i> 
                            Assignment Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Assignment Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" required placeholder="Enter assignment name...">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Instructions</label>
                            <textarea class="form-control" rows="6" name="instructions" placeholder="Enter instructions for students..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Due Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="duedate" required>
                        </div>
                    </div>
                </div>
            </div>
    
            <div class="col-lg-8">
                <div class="card form-card">
                    <div class="card-header">
                        <h5>
                            <i class="fa fa-check-circle"></i> 
                            Selected Resources
                        </h5>
                    </div>
                    <div class="card-body">
                        <div id="selected-resources">
                            <p class="text-muted mb-0">No resources selected.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    
        <div class="card resource-list-card mt-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5>
                    <i class="fa fa-database"></i> 
                    Attach Resources
                </h5>
                <span class="badge-resource-count">'.$totalrecords.' Resources</span>
            </div>
            <div class="card-body">
                <div class="search-resource-box mb-3">
                    <div class="input-group">
                        <input type="text" id="resource-search" class="form-control" placeholder="Search resources by title or ID...">
                        <button type="button" class="btn btn-outline-primary">
                            <i class="fa fa-search"></i>
                        </button>
                    </div>
                </div>
';

if (empty($hits)) {
    echo '<p class="text-muted text-center py-4">No resources found.</p>';
} else {
    echo '<div class="resource-list">';
    foreach ($hits as $hit) {
        $recordid = $hit['id'] ?? '';
        $title = $hit['metadata']['title'] ?? 'Untitled';
        echo '
            <div class="resource-item" data-title="'.strtolower(s($title)).'" data-id="'.strtolower(s($recordid)).'">
                <input class="form-check-input" type="checkbox" name="resources['.s($recordid).']" value="'.s($title).'" id="res-'.s($recordid).'">
                <label class="form-check-label" for="res-'.s($recordid).'">
                    <strong>'.s($title).'</strong>
                    <br>
                    <small class="text-muted">'.$recordid.'</small>
                </label>
            </div>
        ';
    }
    echo '</div>';
}

if ($totalpages > 1) {
    echo '<nav class="assignment-pagination mt-4">';
    echo '<ul class="pagination justify-content-center">';
    if ($page > 1) {
        $prevurl = new moodle_url(
            '/local/inveniordm/lecturer/create_assignment.php',
            ['courseid' => $courseid, 'page' => $page - 1, 'searchresource' => $searchresource]
        );
        echo '<li class="page-item"><a class="page-link" href="'.$prevurl.'">Previous</a></li>';
    }
    for ($i = 1; $i <= $totalpages; $i++) {
        $url = new moodle_url(
            '/local/inveniordm/lecturer/create_assignment.php',
            ['courseid' => $courseid, 'page' => $i, 'searchresource' => $searchresource]
        );
        $active = ($i == $page) ? ' active' : '';
        echo '<li class="page-item'.$active.'"><a class="page-link" href="'.$url.'">'.$i.'</a></li>';
    }
    if ($page < $totalpages) {
        $nexturl = new moodle_url(
            '/local/inveniordm/lecturer/create_assignment.php',
            ['courseid' => $courseid, 'page' => $page + 1, 'searchresource' => $searchresource]
        );
        echo '<li class="page-item"><a class="page-link" href="'.$nexturl.'">Next</a></li>';
    }
    echo '</ul></nav>';
}

echo '
            </div>
        </div>
        <div class="text-end mt-4">
            <button class="btn btn-primary btn-lg save-btn" type="submit">
                <i class="fa fa-save me-2"></i> Save Assignment
            </button>
        </div>
    </form>
    </div>
';

echo '
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        var search = document.getElementById("resource-search");
        var resources = document.querySelectorAll(".resource-item");
        var selectedBox = document.getElementById("selected-resources");
    
        function updateSelectedResources() {
            var checked = document.querySelectorAll(".form-check-input:checked");
            if (checked.length === 0) {
                selectedBox.innerHTML = "<p class=\"text-muted mb-0\">No resources selected.</p>";
                return;
            }
            var html = "";
            checked.forEach(function(item) {
                html += "<div class=\"selected-resource\"><i class=\"fa fa-file\"></i> " + item.value + "</div>";
            });
            selectedBox.innerHTML = html;
        }
    
        resources.forEach(function(resource) {
            var checkbox = resource.querySelector(".form-check-input");
            if (checkbox) {
                checkbox.addEventListener("change", updateSelectedResources);
            }
        });
    
        if (search) {
            search.addEventListener("keyup", function() {
                var keyword = this.value.toLowerCase();
                resources.forEach(function(item) {
                    var title = item.dataset.title || "";
                    var id = item.dataset.id || "";
                    if (title.indexOf(keyword) !== -1 || id.indexOf(keyword) !== -1) {
                        item.style.display = "";
                    } else {
                        item.style.display = "none";
                    }
                });
            });
        }
    });
    </script>
';

echo $OUTPUT->footer();