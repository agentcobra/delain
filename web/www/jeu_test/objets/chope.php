<?php 
include "../verif_connexion.php";
include '../../includes/template.inc';

$t = new template('..');
$t->set_file('FileRef','../template/delain/general_jeu.tpl');
// chemins
$t->set_var('URL',$type_flux.G_URL);
$t->set_var('URL_IMAGES',G_IMAGES);

$contenu_page = '';

// ON VRERIFIE SI L'OBJET EST BIEN DANS L'INVENTAIRE.
$bd=new base_delain;
$req_matos = "select perobj_obj_cod from perso_objets,objets "
. "where perobj_obj_cod = obj_cod and perobj_perso_cod = $perso_cod and obj_gobj_cod = 409 ";
$bd->query($req_matos);
if(!($bd->next_record())){
  // PAS D'OBJET.
 	$contenu_page .= "<p>Hélas... aucune choppe pleine ne se trouve dans votre inventaire !</p>";
} else {
  $num_obj =   $bd->f("perobj_obj_cod");
  // TRAITEMENT DES ACTIONS.
	if(isset($_POST['methode'])){
		$req_pa = "select perso_pa from perso where perso_cod = $perso_cod";
		$bd->query($req_pa);
		$bd->next_record();
		if ($bd->f("perso_pa") < 1)
		{
			$contenu_page .= '<p><b>Vous n’avez pas assez de PA !</b></p>';
		}
		else
		{
      // ON ENLEVE LES PAs
			$req_enl_pa = "update perso set perso_pa = perso_pa - 1 where perso_cod = $perso_cod";
			$bd->query($req_enl_pa);

			// ON SUPPRIME L'OBJET.
			$req_supr_obj = "select  f_del_objet($num_obj)";
			$bd->query($req_supr_obj);
			// ON CREE LA CHOPPE VIDE
			$req_supr_obj = "select  cree_objet_perso(410,$perso_cod)";
			$bd->query($req_supr_obj);

			//INSERTION DU BONUS
			$req_bonus = 'select ajoute_bonus(' . $perso_cod . ',\'ALC\',2, 0.2 + valeur_bonus(' . $perso_cod . ', \'ALC\'))';
            $bd->query($req_bonus);

            // INSERTION DE L'EVENT
            $req_bonus = "insert into ligne_evt(levt_tevt_cod,levt_date,levt_perso_cod1,levt_texte,levt_lu,levt_visible)".
                        "values(69,now(),$perso_cod,'	[perso_cod1] a bu une chope de bière.','O','O')";
            $bd->query($req_bonus);

			$contenu_page .= '<p><b>Vous descendez le verre d’un trait, quel délice !</b></p>';
		}
	}  else { //Not isset ('methode')
		$contenu_page .= '<p align="center">
			Une mousse légère couvre cette boisson aux reflets de miel... non vous ne rêvez pas c’est bien le breuvage des dieux !<br>

			<form method="post" action="chope.php">
			<input type="hidden" name="methode" value="boire">
			<input type="submit" value="Boire (1PA)"  class="test">
			</form>
			</p>';
	}
}

// on va maintenant charger toutes les variables liées au menu
include('../variables_menu.php');

$t->set_var("CONTENU_COLONNE_DROITE",$contenu_page);
$t->parse("Sortie","FileRef");
$t->p("Sortie");