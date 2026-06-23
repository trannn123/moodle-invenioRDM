<?php

namespace local_inveniordm\form;
defined('MOODLE_INTERNAL') || die();
require_once($GLOBALS['CFG']->libdir . '/formslib.php');

class upload_form extends \moodleform {
    public function definition() {
        $mform = $this->_form;


        $backurl = new \moodle_url('/local/inveniordm/index.php');
        $mform->addElement('html', '
            <div style="margin-bottom:20px;">
                <a href="'.$backurl->out(false).'" class="btn btn-secondary">
                    <i class="fa fa-arrow-left"></i>
                    Back
                </a>
            </div>
        ');

        $mform->addElement('header', 'generalinfo', 'General Information');

        $mform->addElement('text', 'title', 'Title');
        $mform->setType('title', PARAM_TEXT);

        $mform->addElement('textarea', 'description', 'Description', ['rows' => 5, 'cols' => 60]);
        $mform->setType('description', PARAM_TEXT);

        $mform->addElement('text', 'free_keyword', 'Keywords');
        $mform->setType('free_keyword', PARAM_TEXT);

        $mform->addElement('select', 'language', 'Language', ['vi' => 'Vietnamese', 'en' => 'English', 'fr' => 'French']);

        $mform->addElement('select', 'documentary_type', 'Documentary Type',
            [
                'collection' => 'Collection',
                'dataset' => 'Dataset',
                'event' => 'Event',
                'image' => 'Image',
                'moving image' => 'Moving Image',
                'still image' => 'Still Image',
                'software' => 'Software',
                'physical object' => 'Physical Object',
                'interactive resource' => 'Interactive Resource',
                'service' => 'Service',
                'sound' => 'Sound',
                'text' => 'Text'
            ]
        );

        $mform->addElement('header', 'technicalinfo', 'Technical Information');

        $mform->addElement('select', 'format', 'Format',
            [
                'pdf' => 'PDF',
                'doc' => 'DOC',
                'docx' => 'DOCX',
                'xls' => 'XLS',
                'xlsx' => 'XLSX',
                'ppt' => 'PPT',
                'pptx' => 'PPTX',
                'odt' => 'ODT',
                'jpg' => 'JPG',
                'jpeg' => 'JPEG',
                'png' => 'PNG',
                'zip' => 'ZIP'
            ]
        );
        $mform->setType('format', PARAM_TEXT);

        $mform->addElement('text', 'location', 'Location URL');
        $mform->setType('location', PARAM_URL);

        $mform->addElement('header', 'educationalinfo', 'Educational Information');

        $mform->addElement(
            'select',
            'learning_resource_type',
            'Learning Resource Type',
            [
                'exercise' => 'Exercise',
                'simulation' => 'Simulation',
                'demonstration' => 'Demonstration',
                'questionnaire' => 'Questionnaire',
                'exam' => 'Exam',
                'assessment' => 'Assessment',
                'experiment' => 'Experiment',
                'lesson' => 'Lesson',
                'animation' => 'Animation',
                'tutorial' => 'Tutorial',
                'glossary' => 'Glossary',
                'guide' => 'Guide',
                'reference material' => 'Reference Material',
                'methodology' => 'Methodology',
                'tool' => 'Tool',
                'teaching scenario' => 'Teaching Scenario',
                'self-assessment' => 'Self Assessment',
                'problem statement' => 'Problem Statement'
            ]
        );

        $mform->addElement('select', 'target_audience', 'Target Audience',
            [
                'teacher' => 'Teacher',
                'author' => 'Author',
                'learner' => 'Learner',
                'administrator' => 'Administrator'
            ]
        );

        $mform->addElement('select', 'educational_level', 'Educational Level',
            [
                'school education' => 'School Education',
                'higher education' => 'Higher Education',
                'vocational training' => 'Vocational Training',
                'primary education' => 'Primary Education',
                'secondary education' => 'Secondary Education',
                'bachelor\'s degree' => 'Bachelor Degree',
                'master\'s degree' => 'Master Degree',
                'doctorate' => 'Doctorate',
                'continuing education' => 'Continuing Education',
                'in-company training' => 'In-company Training'
            ]
        );

        $mform->addElement('select', 'induced_activity', 'Induced Activity',
            [
                'facilitate' => 'Facilitate',
                'learn' => 'Learn',
                'collaborate' => 'Collaborate',
                'communicate' => 'Communicate',
                'cooperate' => 'Cooperate',
                'create' => 'Create',
                'exchange' => 'Exchange',
                'read' => 'Read',
                'observe' => 'Observe',
                'organise' => 'Organise',
                'produce' => 'Produce',
                'publish' => 'Publish',
                'research' => 'Research',
                'self-study' => 'Self Study',
                'practise' => 'Practise',
                'find out' => 'Find Out',
                'train' => 'Train',
                'simulate' => 'Simulate',
                'assess' => 'Assess'
            ]
        );

        $mform->addElement('header', 'rightsinfo', 'Rights');

        $mform->addElement('select', 'copyright', 'Copyright', ['yes' => 'Yes', 'no' => 'No']);

        $mform->addElement('header', 'classificationinfo', 'Classification');

        $mform->addElement('select', 'objective', 'Objective',
            [
                'discipline' => 'Discipline',
                'concept' => 'Concept',
                'prerequisite' => 'Prerequisite',
                'learning objective' => 'Learning Objective',
                'accessibility restrictions' => 'Accessibility Restrictions',
                'educational level' => 'Educational Level',
                'proficiency level' => 'Proficiency Level',
                'security level' => 'Security Level',
                'competency' => 'Competency'
            ]
        );

        $mform->addElement('text', 'taxon_entry', 'Taxon Entry');
        $mform->setType('taxon_entry', PARAM_TEXT);

        $mform->addElement('header', 'lifecycleinfo', 'Life Cycle');

        $mform->addElement('select', 'role', 'Role',
            [
                'author' => 'Author',
                'publisher' => 'Publisher',
                'graphic designer' => 'Graphic Designer',
                'instructional designer' => 'Instructional Designer',
                'contributor' => 'Contributor',
                'subject matter expert' => 'Subject Matter Expert',
                'content provider' => 'Content Provider',
                'technical implementer' => 'Technical Implementer',
                'editor-in-chief' => 'Editor-in-Chief',
                'validator' => 'Validator'
            ]
        );

        $mform->addElement('text', 'entity', 'Contributor');
        $mform->setType('entity', PARAM_TEXT);

        $mform->addElement('date_selector', 'date', 'Date');

        $mform->addElement('header', 'relationinfo', 'Relation');

        $mform->addElement('select', 'relation', 'Relation',
            [
                'is a part of' => 'Is A Part Of',
                'contains' => 'Contains',
                'is a version of' => 'Is A Version Of',
                'requires' => 'Requires',
                'is required by' => 'Is Required By',
                'is associated with' => 'Is Associated With',
                'is based on' => 'Is Based On',
                'is a prerequisite for' => 'Is A Prerequisite For'
            ]
        );

        $mform->addElement('header', 'metainfo', 'Metadata');

        $mform->addElement('select', 'metadata_accessibility', 'Metadata Accessibility',
            [
                'public access' => 'Public Access',
                'restricted access' => 'Restricted Access',
                'read-only' => 'Read Only'
            ]
        );

        $mform->addElement('filepicker', 'resourcefile', 'Upload Resource File');

        $this->add_action_buttons(true, 'Upload Resource');

        // js realtime validation
        $mform->addElement('html', '
        <style>
            .error-border input, .error-border textarea {
                border: 2px solid #da4453 !important;
                background-color: #fff8f8 !important;
            }
            .error-msg {
                color: #da4453;
                font-size: 12px;
                margin-top: 4px;
                display: block;
            }
        </style>
        
        <script>
        (function() {
            var rules = {
                title: { check: function(v) { return v.trim().length >= 3; }, msg: "Title must be at least 3 characters" },
                description: { check: function(v) { return v.trim().length >= 10; }, msg: "Description must be at least 10 characters" },
                keywords: { check: function(v) { return v.trim().length > 0; }, msg: "Keywords are required" }
            };
            
            function validateField(id) {
                var input = document.getElementById("id_" + id);
                if (!input) return;
                var rule = rules[id];
                if (!rule) return;
                var isValid = rule.check(input.value);
                var fitem = input.closest(".fitem");
                if (fitem) {
                    var oldMsg = fitem.querySelector(".error-msg");
                    if (!isValid) {
                        fitem.classList.add("error-border");
                        if (!oldMsg) {
                            var msg = document.createElement("div");
                            msg.className = "error-msg";
                            msg.innerText = rule.msg;
                            input.closest(".felement").appendChild(msg);
                        }
                    } else {
                        fitem.classList.remove("error-border");
                        if (oldMsg) oldMsg.remove();
                    }
                }
            }
            
            document.addEventListener("DOMContentLoaded", function() {
                for (var id in rules) {
                    var el = document.getElementById("id_" + id);
                    if (el) {
                        el.addEventListener("input", function(id) { return function() { validateField(id); }; }(id));
                        el.addEventListener("blur", function(id) { return function() { validateField(id); }; }(id));
                    }
                }
            });
        })();
        </script>
        ');
    }

    public function validation($data, $files) {
        $errors = [];

        if (strlen(trim($data['title'])) < 3) {
            $errors['title'] = 'Title must contain at least 3 characters.';
        }

        if (!empty($data['description']) && strlen(trim($data['description'])) < 10) {
            $errors['description'] = 'Description must contain at least 10 characters.';
        }
        return $errors;
    }
}