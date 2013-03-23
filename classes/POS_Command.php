<?php
/**
 * @file
 * POS_Command is the generic class from which all other commands derive.
 */

abstract class POS_Command {
  protected $input_pattern;
  protected $name;
  protected $id;
  protected $config = array();

  /**
   * Create a new POS_Command
   *
   * @param $name
   *  The human readable name of this command.
   * @param $id
   *  The unique ID (machine readable) of this command.
   * @param $input_pattern
   *  The input pattern to use for this command.
   * @param array $options
   *  Any additional options to provide the command.
   */
  function __construct($name, $id, $input_pattern, array $options = array()) {
    $this->name = $name;
    $this->id = $id;
    $this->input_pattern = $input_pattern;
    $this->config = $options + $this->config;
  }

  /**
   * Get the input pattern that was given for this command.
   *
   * @return mixed
   */
  public function getInputPattern() {
    return $this->input_pattern;
  }

  /**
   * Get the ID of this command.
   *
   * @return mixed
   */
  public function getId() {
    return $this->id;
  }

  /**
   * Get the human readable name of this command.
   *
   * @return mixed
   */
  public function getName() {
    return $this->name;
  }

  /**
   * For a given input, parse it according to the input pattern.
   *
   * This takes a string like "OR1" (load order 1)
   * and turns it into 1, which can then be passed to execute().
   *
   * @param $input
   *
   * @return mixed
   */
  public function deconstructInputFromPattern($input) {
    $pattern = str_replace('%s', '(\S+)', $this->input_pattern);
    preg_match('/^' . $pattern . '$/', $input, $matches);
    return isset($matches[1]) ? $matches[1] : '';
  }

  /**
   * Check whether this command wants to act on a given input.
   *
   * @param $input
   *  The textual input to check.
   *
   * @return bool
   *  A boolean indicator of whether the current command should act on the given input.
   */
  public function shouldRun($input) {
    $pattern = str_replace('%s', '\S+', $this->input_pattern);
    return preg_match('/^' . $pattern . '$/', $input);
  }

  protected function constructInputFromPattern($input = NULL) {
    // If the command has no input pattern, or has one, but it requires input and there is none...
    if(!$this->input_pattern || (strpos($this->input_pattern, '%s') !== FALSE && !isset($input))) {
      return FALSE;
    }
    return str_replace('%s', $input, $this->input_pattern);
  }

  /**
   * Get an HTML representation of the command, for placement on the interface.
   *
   * @return string
   */
  public function getButton($text = NULL, $input = NULL, $options = array()) {
    if($pattern = $this->constructInputFromPattern($input)) {
      static $token = NULL;
      if(!$token) {
        $token = drupal_get_token('pos_command');
      }
      $text = !empty($text) ? $text : $this->name;

      return l($text, 'admin/commerce/pos', drupal_array_merge_deep(array(
        'attributes' => array(
          'class' => array('pos-button'),
          'data-pos-input' => $pattern,
          'id' => 'pos-button-' . $this->id,
          'data-pos-submit' => 'true',
        ),
        'query' => array(
          'command' => $pattern,
          'token' => $token,
        )
      ), $options));
    }
    return FALSE;
  }

  /**
   * Determine if this command is accessible for the given input/state.
   *
   * Returning false here will cause the command to be unusable, and the
   * button will be hidden as well.
   *
   * @param $input
   *  Any input that has been entered.  This may or may not actually be set,
   *  depending on the context it is called in.
   *
   * @param POS_State $state
   *  The current state of the POS system.
   *
   * @return bool
   *  TRUE|FALSE
   */
  public abstract function access($input, POS_State $state);

  /**
   * Run this command for a given input.
   *
   * It is expected that all commands will make changes to the POS_State.
   *
   * @param $input
   *  The textual input.
   *
   * @param POS_State $state
   *  The state object currently representing the POS.
   *
   * @return mixed
   */
  public abstract function execute($input, POS_State $state);
}
