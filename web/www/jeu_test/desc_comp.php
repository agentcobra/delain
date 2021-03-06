<?php 
include_once "verif_connexion.php";
include '../includes/template.inc';
$t = new template;
$t->set_file('FileRef','../template/delain/general_jeu.tpl');
// chemins
$t->set_var('URL',$type_flux.G_URL);
$t->set_var('URL_IMAGES',G_IMAGES);
// on va maintenant charger toutes les variables liées au menu
include('variables_menu.php');

//
//Contenu de la div de droite
//
$contenu_page = '';
ob_start();

$comp = array();

$comp[0] = "<b>Attaque foudroyante :</b> <u>Attention ! Compétence réservée au combat au corps-à-corps</u><br />
Permet d’attaquer pour un coût réduit en PA.<br />
Niv. 1 : l’attaque fait la moitié des dommages normaux.<br>
Niv. 2 : l’attaque fait les trois quarts des dommages normaux.<br>
Niv. 3 : l’attaque fait la totalité des dommages normaux.";

$comp[1] = "<b>Feinte :</b><br>La cible doit réussir une esquive spéciale pour esquiver le coup.<br>
Niv. 1 : l’attaque coûte 3 PA de plus qu’une attaque normale.<br>
Niv. 2 : l’attaque coûte 1 PA de plus qu’une attaque normale.<br>
Niv. 3 : l’attaque coûte autant qu’une attaque normale.";

$comp[2] = "<b>Coup de grâce :</b><br>Les dégâts sont au maximum.<br>
Niv. 1 : l’attaque coûte 3 PA de plus qu’une attaque normale.<br>
Niv. 2 : l’attaque coûte 1 PA de plus qu’une attaque normale.<br>
Niv. 3 : l’attaque coûte autant qu’une attaque normale.";

$comp[3] = "<b>Réceptacle magique :</b><br />Lors du lancement d’un sort hermetique ou d’esprit (donc pas de magie divine), au lieu de choisir une cible, on peut lancer le sort dans un réceptacle magique. Tous les contrôles liés au lancement de sort sont fait à ce moment, ainsi que le gain de PX. Plus tard, le lanceur peut lancer le sort stocké dans le réceptacle en n’utilisant que 2 ou 4 PAs suivant la puissance du sort. Aucun jet de dés n’est fait au moment du lancement du sort lié à un réceptacle.";

$comp[4] = "<b>Bout portant :</b> <u>Attention ! Compétence réservée au combat à distance</u><br />
Permet de réduire le malus en cas d’attaque avec une arme à distance sur la même case.<br>
Niv. 1 : réduit le malus de 5%.<br>
Niv. 2 : réduit le malus de 15%.<br>
Niv. 3 : annule le malus.";

$comp[5] = "<b>Tir précis :</b> <u>Attention ! Compétence réservée au combat à distance</u><br />
L’attaque atteint la cible désignée même si elle est surchargée.<br>
Niv. 1 : l’attaque coûte 3 PA de plus qu’une attaque normale.<br>
Niv. 2 : l’attaque coûte 1 PA de plus qu’une attaque normale.<br>
Niv. 3 : l’attaque coûte autant qu’une attaque normale.";
?>

<p><b>Compétences spéciales</b></p><br />
<p>Les compétences spéciales, et leurs évolutions, décrites ici ne sont accessibles que dans la limite de une pour sept niveaux du personnage. Les Nains, qui connaissent par défaut Attaque foudroyante, ont donc le droit à une compétence de plus à niveau égal. De plus, les compétéences de combat (hors réceptacles) ne sont utilisables que deux fois par tour de jeu.</p><br />
<p><?php echo $comp[0]; ?></p><br />
<p><?php echo $comp[1]; ?></p><br />
<p><?php echo $comp[2]; ?></p><br />
<p><?php echo $comp[3]; ?></p><br />
<p><?php echo $comp[4]; ?></p><br />
<p><?php echo $comp[5]; ?></p><br />
<p style="text-align:center;"><a href="niveau.php">Retour !</a></p>
<?php 
$contenu_page = ob_get_contents();
ob_end_clean();
$t->set_var("CONTENU_COLONNE_DROITE",$contenu_page);
$t->parse('Sortie','FileRef');
$t->p('Sortie');
?>
