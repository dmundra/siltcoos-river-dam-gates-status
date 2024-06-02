<?php

namespace Drupal\tragedy_commons\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines landing pages for various games.
 */
class DefaultController extends ControllerBase {

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilder
   */
  protected $formBuilder;

  /**
   * ModalFormContactController constructor.
   *
   * @param \Drupal\Core\Form\FormBuilder $form_builder
   *   The form builder.
   */
  public function __construct(FormBuilder $form_builder) {
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The Drupal service container.
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder')
    );
  }

  /**
   * Main game.
   */
  public function commons() {
    return [
      '#type' => 'markup',
      '#markup' => 'These are some games that I have created to be played by
students in a class to illustrate the tragedy of the commons, made famous by
<a href="https://www.jstor.org/stable/1724745">Garrett Hardin in 1968</a> in
Science magazine. If you want to get a feel for the game, you can try
single-person versions of these Tragedy of the Commons games by clicking below:',
    ];
  }

  /**
   * Prisoners Dilemma Game 1.
   */
  public function pdgame1() {
    $output = [];

    $output['intro'] = [
      '#type' => 'markup',
      '#markup' => '<p><iframe width="560" height="315"
src="https://www.youtube.com/embed/TJCGTNIwmv8" title="YouTube video player"
allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
allowfullscreen></iframe></p><p>Then try it below. This is a game of strategy.
<font color=blue>YOU</font> choose your strategy from the COLUMNs.
<font color=#b50000>PARTNER</font> chooses strategy from ROWs. Think about your
strategy and play to win!! [Note: in the video above, "You" is making the ROW
choices. But the logic is symmetric, so this doesn\'t affect the game at all.]</p><p></p>',
      '#allowed_tags' => [
        'p',
        'iframe',
        'font',
      ],
    ];

    $output['intro']['#attached']['library'][] = 'tragedy_commons/pdgame';

    $output['form'] = $this->formBuilder->getForm('Drupal\tragedy_commons\Form\PDGame1Form');

    return $output;
  }

  /**
   * Prisoners Dilemma Game 2.
   */
  public function pdgame2() {
    $output = [];

    $output['intro'] = [
      '#type' => 'markup',
      '#markup' => '<p>PS205: Introduction to International Relations Rules for
the Prisoners Dilemma Game (written by Jane Dawson, University of Oregon,
Department of Political Science, 1998) This is a game of strategy. The pairs are
ordered (Row,Column). Think about your strategy and play to win!! Those who
receive the lowest prison terms--due to their finely honed rational skills--will
be the winners. Good luck!</p><h2>Payoff Structure</h2>
<table border="5">
  <tr>
    <td colspan=2 rowspan=2>&nbsp;</td>
    <td colspan=2 id="column">Column</td>
  </tr>
  <tr>
    <td>Cooperate</td>
    <td>Defect</td>
  </tr>
  <tr>
    <td class="vertical" id="row" rowspan=2>Row</td>
    <td class="vertical">Cooperate</td>
    <td colspan=2 rowspan=2>
      <table border=5>
        <tr>
          <td id="ul">2,2</td>
          <td id="ur">10,0</td>
        </tr>
        <tr>
          <td id="ll">0,10</td>
          <td id="lr">5,5</td>
        </tr>
      </table>
    </td>
  </tr>
  <tr>
    <td class="vertical">Defect</td>
  </tr>
</table>',
    ];

    $output['intro']['#attached']['library'][] = 'tragedy_commons/pdgame';

    $output['form'] = $this->formBuilder->getForm('Drupal\tragedy_commons\Form\PDGame2Form');

    return $output;
  }

}
