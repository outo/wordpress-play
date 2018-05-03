<?php
/*
Plugin Name: Pain Inducer
*/

require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
require_once(plugin_dir_path( __FILE__ ) . '/dataset.php');

class WPPainInducer
{

    private $painLimiter;
    private $dataset;
    const DEFAULT_PROMPT = "what sort of pain would you like to experience?";

    public function __construct()
    {
        //associate shortcode 'induce_pain' with a method 'shortcode' in this class
        add_shortcode('induce_pain', array($this, 'shortcode'));
    }

    /**
     * Each usage of shortcode 'induce_pain' will invoke this function.
     * @param $atts - attributes passed into shortcode
     * @param $content - contents of shortcode (between 'tags'), here it will be prompt
     * @return string - html snippet to insert instead of shortcode
     */
    public function shortcode($atts, $content)
    {
        //if shortcode does not have contents then use default prompt
        if (empty($content)) {
            $content = WPPainInducer::DEFAULT_PROMPT;
        }

        $args = $this->processShortcodeAtts($atts);
        $this->initialise($args);

        // don't send html contents back just yet
        ob_start();

        // an inline form to capture user's input. It uses prompt stored in $content.
        ?>
        <form action="#v_form" method="post" id="v_form">
            <label for="pain_target"><h2><?php echo $content ?></h2></label>
            <input type="text" name="pain_target" id="pain_target"/>
            <input type="submit" name="submit_form" value="submit"/>
        </form>
        <?php

        // return the buffer contents and clean it so that we have explicit control over what is sent back
        $html = ob_get_clean();

        // if user clicked 'submit'
        if (isset($_POST["submit_form"])) {
            $validationError = $this->handleFormSubmission();

            if (empty($validationError)) {
                // no validation error message
                $html .= '<p style="color:green"> submitted successfully</p>';
            } else {
                // :( something went wrong
                $html .= '<p style="color:orange"> submission failed: ' . $validationError . '</p>';
            }
        }

        // fetch data and present in tabular format
        $data = $this->dataset->select($this->painLimiter);
        $html .= $this->presentData($data);

        return $html;
    }


    /**
     * Initialise this object and its initialise collaborators.
     * @param $args
     */
    private function initialise($args) {
        $this->painLimiter = $this->sanitise($args['pain_limiter']);

        $this->dataset = new WPPainInducerDataset($this->sanitise($args['table_name']));
        $this->dataset->initialise();
    }

    /**
     * It process the parameters passed in the shortcode and will supplement default values (both optional)
     * @param $atts - attributes passed into shortcode
     * @return mixed - processed attributes
     */
    private function processShortcodeAtts($atts) {
        $args = shortcode_atts(
            array(
                'table_name' => 'pain_table',
                'pain_limiter' => 4,
            ), $atts);

        return $args;
    }

    /**
     * Never trust user's input! To prevent all sorts of injections, sanitise it.
     * This is only (!) protecting against injection of HTML and PHP tags (e.g. <script>)
     * @param $value
     * @return string
     */
    private function sanitise($value)
    {
        //FIXME:cater for script injections as well, if you invoke system commands
        return strip_tags($value, "");
    }


    /**
     * Validate the posted HTML form, sanitise and persist in database
     * @return null|string - validation/persistence error message or null if succeeded
     */
    private function handleFormSubmission()
    {
        $validationError = $this->validateForm();

        if (!empty($validationError)) {
            return $validationError;
        }

        $sanitisedPainTarget = $this->sanitise($_POST["pain_target"]);
        if ($this->dataset->insert($sanitisedPainTarget) === false) {
            return '<span class="baaad">Database insert failed</span>';
        }

        return null;
    }


    /**
     * Check that the posted field is set in posted HTML form
     * @return null|string - validation error message or null if succeeded
     */
    private function validateForm()
    {
        if ($_POST["pain_target"] == "") {
            return "I need to know what pain you wish to induce";
        }
        return null;
    }


    /**
     * Transform rows of database resultset into an HTML's tabular format
     * @param $rows - db data
     * @return string - html with tabular data
     */
    private function presentData($rows)
    {
        $htmlTable = "<table><thead><th>ID</th><th>INDUCED PAIN TYPE</th></thead><tbody>";
        foreach ($rows as $k => $v) {
            $htmlTable .= "<tr><td>$v->id</td><td>$v->type_of_pain</td></tr>";
        }

        return $htmlTable . "</tbody></table>";
    }
}

$wpPainInducer = new WPPainInducer();
