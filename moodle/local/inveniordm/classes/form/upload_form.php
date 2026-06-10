<?php

namespace local_inveniordm\form;
defined('MOODLE_INTERNAL') || die();
require_once($GLOBALS['CFG']->libdir . '/formslib.php');

class upload_form extends \moodleform {
    public function definition() {
        $mform = $this->_form;
        $mform->addElement('header', 'basicinfo', 'Basic Information');

        // Title
        $mform->addElement('text', 'title', 'Resource Title');
        $mform->setType('title', PARAM_TEXT);

        // Description
        $mform->addElement('textarea', 'description', 'Description', ['rows' => 6, 'cols' => 60]);
        $mform->setType('description', PARAM_TEXT);

        // Language
        $mform->addElement('select', 'language', 'Language', ['en' => 'English', 'vi' => 'Vietnamese']);

        // Format
        $mform->addElement('select', 'format', 'Format', ['pdf' => 'PDF', 'video' => 'Video', 'doc' => 'DOC']);

        // Document Type
        $mform->addElement('select', 'documenttype', 'Document Type', ['text' => 'Text', 'dataset' => 'Dataset', 'image' => 'Image', 'video' => 'Video']);

        // Discipline
        $mform->addElement('select', 'discipline', 'Discipline', [
            'Artificial Intelligence' => 'Artificial Intelligence',
            'Computer Networking' => 'Computer Networking',
            'Cyber Security' => 'Cyber Security'
        ]);

        // Educational Level
        $mform->addElement('select', 'educationallevel', 'Educational Level', ['bachelor' => 'Bachelor', 'master' => 'Master']);

        // Target Audience
        $mform->addElement('select', 'targetaudience', 'Target Audience', ['learner' => 'Learner', 'teacher' => 'Teacher']);

        // Learning Resource Type
        $mform->addElement('select', 'learningresourcetype', 'Learning Resource Type', ['lesson' => 'Lesson', 'tutorial' => 'Tutorial', 'lab' => 'Lab', 'exercise' => 'Exercise']);

        // Keywords
        $mform->addElement('text', 'keywords', 'Keywords');
        $mform->setType('keywords', PARAM_TEXT);

        // Copyright
        $mform->addElement('select', 'copyright', 'Copyright', ['yes' => 'Yes', 'no' => 'No']);

        // Relation
        $mform->addElement('text', 'relation', 'Relation');
        $mform->setType('relation', PARAM_TEXT);

        // File Upload
        $mform->addElement('filepicker', 'resourcefile', 'Upload File');

        // Submit Button
        $this->add_action_buttons(true, 'Upload Resource');

        // JS realtime validation
        $mform->addElement('html', '
        
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
}