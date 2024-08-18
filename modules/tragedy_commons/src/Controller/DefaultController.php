<?php

namespace Drupal\tragedy_commons\Controller;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Url;
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
    $output = [];

    $request = new Url('tragedy_commons.requests');
    $output['intro'] = [
      '#type' => 'markup',
      '#markup' => new FormattableMarkup('<p>These are some games that I
have created to be played by students in a class to illustrate the tragedy of
the commons, made famous by
<a href="https://www.jstor.org/stable/1724745">Garrett Hardin in 1968</a>
in Science magazine. If you want to get a feel for the game, you can try
single-person versions of these Tragedy of the Commons games by clicking below:
</p><h2>Request Form</h2><p>If you are a teacher or professor and want to use
the multi-person version of the game, <a href="@url">Fill out the REQUEST
FORM</a>.</p>', ['@url' => $request->toString()]),
    ];

    $output['single'] = [
      '#type' => 'details',
      '#title' => 'Single person games',
    ];

    $output['single']['list'] = [
      '#theme' => 'item_list',
      '#list_type' => 'ul',
      '#items' => [
        $this->t('A single person game to prepare you for the
Tragedy of the Commons game, also known as <a
href="https://pages.uoregon.edu/rmitchel/commons/1cow/index.shtml"
target="_blank">"Making optimal use of a Private Farm"</a>. This game is NOT a
Tragedy of the Commons - it simply shows you that one problem in avoiding
overuse of an environmental resource involves simply identifying the carrying
capacity of the resource. In this case, you are effectively in the role of a
farmer with a new piece of land that you own privately and the "game" is to
figure out how many cows is the best number to have on the farm - too many and
overgrazing reduces the total milk produced by all cows; too few and you
aren\'t taking full advantage of the farm you bought (although you may want to
keep part of the farm to grow organic vegetables!).'),
        $this->t('The single person <a
href="https://pages.uoregon.edu/rmitchel/commons/1whale/index.shtml"
target="_blank"> Tragedy of Indigenous Whaling</a>. This doesn\'t really have a
solution - I never quite got around to writing the script for it. But it may
give you a sense of the tradeoffs that indigenous cultures MAY face in retaining
a culture while using a scarce natural resource, like whales.'),
      ],
    ];

    $output['multi-into'] = [
      '#type' => 'details',
      '#title' => 'Setting up the Multi-person Tragedy of the Commons game',
    ];

    $output['multi-into']['list'] = [
      '#theme' => 'item_list',
      '#list_type' => 'ul',
      '#items' => [
        $this->t('I can set this up if you are a professor or teacher and
        would like to use it for your class. The REQUEST FORM is the fifth
        bullet below, but please read the other four first.'),
        $this->t('For more about using the multi-person game for
educational purposes, read the <a
href="https://ronaldbmitchell.com/commons/instructions/">instructions</a> before
filling out the request form below.'),
        $this->t('<a
href="https://ronaldbmitchell.com/commons/practice_instructions">Example
pages</a> of the game once its been played.'),
        $this->t('My <a
href="https://ronaldbmitchell.com/wp-content/uploads/2024/01/04-lawecontoc.pdf"
target="_blank">lecture notes</a> and Powerpoint for when I run the game
simulation in my class.'),
        $this->t('<a
href="https://www.youtube.com/watch?v=sRjhm8kUONQ" target="_blank">Video of
simulation</a> I ran while at Stanford University.'),
        $this->t('You may want to
assign <a
href="https://blogs.scientificamerican.com/voices/the-tragedy-of-the-tragedy-of-the-commons/">
Mildenberger\'s 2019 article</a> on racism, Hardin, and the ToC. I assign
students to give me pros and cons as to whether I should teach the Tragedy of
the Commons or not, and always get great feedback.'),
      ],
    ];

    $output['multi-play'] = [
      '#type' => 'details',
      '#title' => 'Playing the Multi-person Tragedy of the Commons game',
    ];

    $multi_player_form = $this->formBuilder->getForm('Drupal\tragedy_commons\Form\MultiPlayerForm');

    $items = [
      $this->t('If your professor has already requested and been approved
      to play the Multi-person Tragedy of the Commons game, please do the
      following:'),
      $this->t('Before playing the multi-person game with other students
in your class, make sure to play the single person game of <a
href="https://pages.uoregon.edu/rmitchel/commons/1cow/index.shtml">"Making
optimal use of a Private Farm"</a> as described directly above.'),
      $this->t('After playing the single person game, you can access the
      multi-person Tragedy of the Commons game for your class by entering the
      password provided by your instructor and hitting ENTER'),
      $multi_player_form,
      $this->t('If there is a problem with the multi-person version,
email me at <a href="mailto:rmitchel@uoregon.edu">rmitchel@uoregon.edu</a>.'),
    ];

    $output['multi-play']['list'] = [
      '#theme' => 'item_list',
      '#list_type' => 'ul',
      '#items' => $items,
    ];

    return $output;
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
