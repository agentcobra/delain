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
if (!isset($methode))
{
	$methode = 'debut';
}
switch($methode)
	{
		case "debut":
?>
		<form name="sitting" method="post" action="<?php echo $PHP_SELF;?>">
		<input type="hidden" name="methode" value="sitting">	
		<table>
		<tr>
		<td><p>Compte du sitteur : </td>
		<td><input type="text" name="compte_sitteur"></td>
		</tr>
		
		<tr>
		<td><p>Nombre d'heures avant que le sitting ne s'active</td>
		<td><input type="text" name="heure_debut"></td>
		</tr>
		
		<tr>
		<td><p>Durée du sitting en heure </td>
		<td align="right"><b><input type="text" name="duree_heure" value="6"> (<i>Minimum 6 heures !</i>)</b></td>
		</tr>
		
		<tr>
		<td><p>Durée du sitting en jour</td>
		<td align="right" ><b><input type="hidden" name="duree_jour" value="5"> (<i>Maximum 5 jours !</i>)</b></td>
		</tr>
		
		<tr>
		<td colspan="2"><p style="text-aligne:center">
		<input type="submit" class="test" value="Valider le sitting !"></p>
		</td>
		</tr>
		</table>
		</form>
		<br><hr><a href="<?php echo $PHP_SELF;?>?methode=liste">Liste des sittings en cours ou prévus pour votre compte</a>
		<br><br><a href="<?php echo $PHP_SELF;?>?methode=ancien">Anciens sittings</a><br><br>
		<hr><a href="<?php echo $PHP_SELF;?>?methode=liste_sitteur">Liste des sittings qui vous ont été demandés et non échus</a>
		<br><br><a href="<?php echo $PHP_SELF;?>?methode=ancien_sitteur">Anciens sittings que vous avez réalisé</a><br><br>
		<?php 
		break;
		
		case "sitting":
		$req = "select compt_cod from compte where compt_nom = '$compte_sitteur'";
		$db->query($req);
		$db->next_record();
		$compte_sitteur = $db->f("compt_cod");
		if ($duree_heure == null or $heure_debut == null or $compte_sitteur == null)
		{
					echo "Il manque des informations pour enregistrer ce sitting";
				if ($duree_heure == null)
				{
					echo "<br>Vous n'avez pas indiqué de durée pour le sitting";
				}
				if ($heure_debut == null)
				{
					echo "<br>Vous n'avez pas déclaré l'heure de déclenchement du sitting";
				}
				if ($compte_sitteur == null)
				{
					echo "<br>Vous n'avez pas déclaré de sitteur pour votre compte";
				}
		}
		else if ($duree_heure < 6)
		{
			echo "<br>Votre durée de sitting est inférieure à 6 heures.";
		}
		else if ($duree_heure > 120)
		{
			echo "<br>Votre durée de sitting est supérieure à 5 jours !";
		}
		else
		{
            // http://www.jdr-delain.net/forum/ftopic10962.php
            // On s'assure que le sitting est autorisé:
            // - Pas plus de 5 sittings sur les 15 derniers jours
            //   (Compté depuis le début du sitting prévu, en remontant)
            $req = 'select count(1) as nsittings from compte_sitting'
                    . ' where csit_compte_sitte = ' . $compt_cod
                    . ' and csit_ddeb >= (now() + \'' . $heure_debut
                    . ' hours\'::interval - \'15 days\'::interval)';
            $db->query($req);
            $db->next_record();
            $nSittings = $db->f('nsittings');
            // - Pas plus de 5 jours sittés sur les 15 derniers jours
            //   (Compté depuis la fin du sitting prévu, en remontant)
            $periode_debut = 'now() + \'' . ($heure_debut + $duree_heure)
                            . ' hours\'::interval - \'15 days\'::interval';
            $req = 'select \'5 days\'::interval - sum(csit_dfin - max(csit_ddeb , '
                    . $periode_debut. ')) as dsittings'
                    . ' from compte_sitting'
                    . ' where csit_compte_sitte = ' . $compt_cod
                    . ' and csit_dfin >= ' . $periode_debut;
            $db->query($req);
            $db->next_record();
            $dSittings = $db->f('dsittings');
            // - Pas d'accumulation de sitting pour le sitteur.
            //   (Le nouveau sitting ne débute pendant aucun des sittings
            //   (programmés, et aucun sitting programmé ne débute pendant
            //   (le nouveau sitting)
            // - Pas d'accumulation de sitteur pour le sitté.
            //   (Cas précédent, appliqué au sitté.)
            // - Pas de chaînes de sitting
            //   (Cas précédents, en échangeant sitteur et sitté.)
            $req = 'select count(1) as bcumul from compte_sitting'
                    . ' where (csit_compte_sitte = ' . $compt_cod
                    . ' or csit_compte_sitte = ' . $compte_sitteur
                    . ' or csit_compte_sitteur = ' . $compt_cod
                    . ' or csit_compte_sitteur = ' . $compte_sitteur . ')'
                    . ' and ((now() + \'' . $heure_debut . ' hours\'::interval)'
                    . ' between csit_ddeb and csit_dfin'
                    . ' or csit_ddeb between'
                    . ' (now() + \'' . $heure_debut . ' hours\'::interval)'
                    . ' and (now() + \'' . ($heure_debut + $duree_heure)
                    . ' hours\'::interval))';
            $db->query($req);
            $db->next_record();
            $bCumul = $db->f('bcumul');
            // - Pas de sitteur venant d'un compte lié.
            //   (Les couples peuvent être sittés, mais ne peuvent sitter
            //   (personne)
            $erreur = 0;
            $contenu_page .= 'Sittings sur 15 jours glissants: <b>' . $nSittings
                . '</b><br />';
            if ($nSittings >= 5)
            {
                // Déjà eu 5 sittings sur les 15 jours précédents.
                // Impossible d'en rajouter un sixième.
                $contenu_page .= '<b>Vous ne pouvez déclarer plus de 5 sittings'
                    . ' sur 15 jours glissants. Cette demande ne peut être'
                    . ' acceptée sans enfreindre la charte.</b><br />';
                $erreur = 1;
            }
            if ($dSittings > 0)
            {
                $contenu_page .= 'Durée de sitting restante sur 15 jours: <b>'
                    . $dSittings . '</b><br />';
                if ($dSittings < 0)
                {
                    $contenu_page .= '<b>La durée maximale de sitting sur 15 jours'
                        . ' glissants est fixée à 5 jours. Cette demande vous ferait'
                        . ' dépasser la durée autorisée, et ne peut donc être'
                        . ' acceptée sans enfreindre la charte.</b><br />';
                    $erreur = 1;
                }
            }
            if ($bCumul != 0)
            {
                $contenu_page .= '<b>Les demandes de sittings ne peuvent être'
                    . ' cumulées à un instant donné, ni chez le sitté, ni chez'
                    . ' le sitteur. Cette demande engendrerait un cumul ou une'
                    . ' chaîne de sittings soit de votre côté, soit du côté du'
                    . ' sitteur, et ne peut donc être acceptée sans enfreindre'
                    . ' la charte.</b><br />';

                // Tentative de suggestion de plage.
                $req = 'select csit_ddeb, csit_dfin'
                        . ' from compte_sitting'
                        . ' where (csit_compte_sitte = ' . $compt_cod
                        . ' or csit_compte_sitteur = ' . $compt_cod . ')'
                        . ' and ((now() + \'' . $heure_debut . ' hours\'::interval)'
                        . ' between csit_ddeb and csit_dfin'
                        . ' or csit_ddeb between'
                        . ' (now() + \'' . $heure_debut . ' hours\'::interval)'
                        . ' and (now() + \'' . ($heure_debut + $duree_heure)
                        . ' hours\'::interval))';
                $db->query($req);
                if ($db->next_record())
                {
                    $contenu_page .= 'Vous avez déjà un sitting prévu entre '
                        . $db->f('csit_ddeb') . ' et ' . $db->f('csit_dfin')
                        . ' qui croise la plage horaire choisie pour ce nouveau'
                        . ' sitting. <br />';
                }

                $req = 'select csit_ddeb, csit_dfin'
                        . ' from compte_sitting'
                        . ' where (csit_compte_sitte = ' . $compte_sitteur
                        . ' or csit_compte_sitteur = ' . $compte_sitteur . ')'
                        . ' and ((now() + \'' . $heure_debut . ' hours\'::interval)'
                        . ' between csit_ddeb and csit_dfin'
                        . ' or csit_ddeb between'
                        . ' (now() + \'' . $heure_debut . ' hours\'::interval)'
                        . ' and (now() + \'' . ($heure_debut + $duree_heure)
                        . ' hours\'::interval))';
                $db->query($req);
                if ($db->next_record())
                {
                    $contenu_page .= 'Votre sitteur a déjà un sitting prévu entre '
                        . $db->f('csit_ddeb') . ' et ' . $db->f('csit_dfin')
                        . ' qui croise la plage horaire choisie pour ce nouveau'
                        . ' sitting. <br />';
                }

                $erreur = 1;
            }
            if ($erreur == 1)
            {
                $contenu_page .= ' Si vous pensez qu\'il y a une erreur, merci '
                    . 'de le signaler sur le forum.';
                echo $contenu_page;
//                 $erreur = 0;
            }
            if ($erreur == 0)
            {
                $declenchement = $duree_heure + $heure_debut;
                $req = "select to_char((now()+'$heure_debut hours'::interval),'DD-MM-YYYY / hh24:mi') as date_deb,to_char((now()+'$declenchement hours'::interval),'DD-MM-YYYY / hh24:mi') as date_fin";
                $db->query($req);
                $db->next_record();
                $date_deb = $db->f("date_deb");
                $req = "select sitting($compte_sitteur,$compt_cod,now() + '$heure_debut hours'::interval,now() + '$declenchement hours'::interval) as resultat";
                $db->query($req);
                $db->next_record();
                $contenu_page .= $db->f("resultat");
                $req = "select compt_nom from compte
                                where compt_cod = $compt_cod";
                $db2->query($req);
                $db2->next_record();
                $compte_sitte_nom = $db2->f("compt_nom");
                $req = "select compt_nom from compte
                                where compt_cod = $compte_sitteur";
                $db2->query($req);
                $db2->next_record();
                $compte_sitteur_nom = $db2->f("compt_nom");
                echo $contenu_page . "<br>Compte sitteur : <b>" . $compte_sitteur_nom . "</b>";
                echo "<br>Nombre d'heures du sitting : <b>" . $duree_heure . " heures</b>";
                echo "<br>Début du sitting : <b>" . $date_deb . "</b>";
                ?>
                <br><a href="<?php echo $PHP_SELF;?>?methode=debut">Retour</a><br><hr>
                <?php 
                $req = "select pcompt_perso_cod from perso_compte where pcompt_compt_cod = $compt_cod order by pcompt_perso_cod limit 1";
                $db->query($req);
                $db->next_record();
                $perso_sit = $db->f("pcompt_perso_cod");
                $req = "select pcompt_perso_cod from perso_compte where pcompt_compt_cod = $compte_sitteur order by pcompt_perso_cod limit 1";
                $db->query($req);
                $db->next_record();
                $perso_sitteur = $db->f("pcompt_perso_cod");
                $req = "select nextval('seq_msg_cod') as numero";
                $db->query($req);
                $db->next_record();
                $num_mes = $db->f("numero");
                //
                $corps = "Le compte $compte_sitte_nom a demandé à être sitté par le compte $compte_sitteur_nom.
                                    <br>Vous pouvez annuler cette demande de sitting si elle n'a pas démarrée.
                                    <br>Nous vous rappelons que la règle est de d'abord se mettre en accord avec son sitteur par l'envoi d'un message.
                                    <br><br><br>
                                    <br>Compte sitteur : <b>$compte_sitteur_nom</b>
                                    <br>Nombre d'heures du sitting : <b>$duree_heure heures</b>
                                    <br>Début du sitting : <b>$date_deb";
                $corps = pg_escape_string($corps);
                $titre = "Déclaration de sitting $compte_sitte_nom / $compte_sitteur_nom";
                $titre = pg_escape_string($titre);
                $length = 50;
                // On vérifie que le titre loge dans la bdd.
                while (strlen($titre) > $length && $length > 0)
                {
                    // Couper sans abîmer les corrections de pg_escape_string
                    $titre = substr($titre , $length);
                    $lastchar = $titre{$length - 1};
                    // On supprime les \ et ' de fin de ligne
                    if ($lastchar === '\\' || $lastchar === '\'')
                    {
                        $length--;
                    }
                }
                $req_ins_mes = "insert into messages (msg_cod,msg_date2,msg_date,msg_titre,msg_corps) values ($num_mes,now(),now(),e'$titre',e'$corps') ";
                $db->query($req_ins_mes);
                /******************************/
                /* On enregistre l'expéditeur */
                /******************************/
                $req_ins_exp = "insert into messages_exp (emsg_cod,emsg_msg_cod,emsg_perso_cod,emsg_archive) values (nextval('seq_emsg_cod'),$num_mes,$perso_sit,'N')";
                $db->query($req_ins_exp);
                $req_ins_dest = "insert into messages_dest (dmsg_cod,dmsg_msg_cod,dmsg_perso_cod,dmsg_lu,dmsg_archive) values (nextval('seq_dmsg_cod'),$num_mes,$perso_sitteur,'N','N')";
                $db->query($req_ins_dest);
            }
		}
		break;

		case 'liste':
		?>
		<br>La liste ci-dessous donne les sittings que vous avez déclarés et qui se réaliseront<br>
		<br><a href="<?php echo $PHP_SELF;?>?methode=debut">Retour</a><br><hr>
		<table>
		<tr><td><b>Compte Sitteur</b></td><td><b>Date de début</b></td><td><b>Date de fin</b></td><td><b>Annulation d'un sitting</b></td></tr>	
		<?php 
		$req = "select to_char(csit_ddeb,'DD-MM-YYYY / HH24:mi') as date_debut,to_char(csit_dfin,'DD-MM-YYYY / HH24:mi') as date_fin,csit_compte_sitteur from compte_sitting
								where csit_compte_sitte = $compt_cod
								and csit_dfin > now()
								and (csit_ddeb + '2 hours'::interval) < now()
								order by csit_ddeb";
		$db->query($req);
		while ($db->next_record())
		{
				$date_deb = $db->f("date_debut");
				$date_fin = $db->f("date_fin");
				$compte_sitteur = $db->f("csit_compte_sitteur");
				$req2 = "select compt_nom from compte
								where compt_cod = $compte_sitteur";
				$db2->query($req2);
				$db2->next_record();
				$compte_sitteur_nom = $db2->f("compt_nom");		
				?>
				<tr><td class="soustitre2"><?php echo $compte_sitteur_nom;?></td>
				<td class="soustitre2"><?php echo $date_deb;?></td>
				<td class="soustitre2"><?php echo $date_fin;?></td>
				<td class="soustitre2"><i>Sitting en cours, il ne peut être annulé</i></td>
				</tr>
				<?php 		
		}
		$req = "select to_char(csit_ddeb,'DD-MM-YYYY / HH24:mi') as date_debut,to_char(csit_dfin,'DD-MM-YYYY / HH24:mi') as date_fin,csit_compte_sitteur,csit_cod,csit_compte_sitte from compte_sitting
								where csit_compte_sitte = $compt_cod
								and (csit_ddeb > now()
								or (csit_ddeb + '2 hours'::interval) > now())
								order by csit_ddeb";
		$db->query($req);
		while ($db->next_record())
		{
				$date_deb = $db->f("date_debut");
				$date_fin = $db->f("date_fin");
				$compte_sitteur = $db->f("csit_compte_sitteur");
				$compte_sitte = $db->f("csit_compte_sitte");
				$csit_cod = $db->f("csit_cod");
				$req2 = "select compt_nom from compte
								where compt_cod = $compte_sitteur";
				$db2->query($req2);
				$db2->next_record();
				$compte_sitteur_nom = $db2->f("compt_nom");		
				?>
				<tr><td class="soustitre2"><?php echo $compte_sitteur_nom;?></td>
				<td class="soustitre2"><?php echo $date_deb;?></td>
				<td class="soustitre2"><?php echo $date_fin;?></td>
				<td class="soustitre2"><a href="<?php echo $PHP_SELF;?>?sit=<?php echo $csit_cod;?>&sit2=<?php echo $compte_sitteur;?>&sit3=<?php echo $compte_sitte;?>&methode=annulation"><i>Annulation de ce sitting</i></a></td>		
				</tr>
				<?php 		
		}
		?>
		</table>
		<?php 
		break;
		
		case 'annulation':
		if ($sit == null)
		{
				echo "Tricher n'est pas un bon concept. Merci pour la trace laissée";
		}
		else
		{
				$req = "delete from compte_sitting where csit_cod = $sit and csit_compte_sitteur = $sit2 ";
				$db->query($req);
				$db->next_record();
						?>
						<br>La demande de sitting antérieure a bien été annulée<br>Un message a été adressé au compte que vous deviez sitter.
						<br><a href="<?php echo $PHP_SELF;?>?methode=debut">Retour</a><br><hr>
						<?php 
				//Envoi d'un message
				$req = "select pcompt_perso_cod from perso_compte where pcompt_compt_cod = $sit3 order by pcompt_perso_cod limit 1";
				$db->query($req);
				$db->next_record();
				$perso_sit = $db->f("pcompt_perso_cod");
				$req = "select pcompt_perso_cod from perso_compte where pcompt_compt_cod = $sit2 order by pcompt_perso_cod limit 1";
				$db->query($req);
				$db->next_record();
				$perso_sitteur = $db->f("pcompt_perso_cod");
				$req = "select nextval('seq_msg_cod') as numero";
				$db->query($req);
				$db->next_record();
				$num_mes = $db->f("numero");
				//
				$corps = "La précédente demande de sitting a été annulée par le demandeur (sitté). L'annulation prend effet immédiatement";
				$corps = pg_escape_string($corps);					
				$titre = "Annulation de sitting par le sitté";
				$req_ins_mes = "insert into messages (msg_cod,msg_date2,msg_date,msg_titre,msg_corps) values ($num_mes,now(),now(),e'$titre',e'$corps') ";
				$db->query($req_ins_mes);
				/******************************/
				/* On enregistre l'expéditeur */
				/******************************/
				$req_ins_exp = "insert into messages_exp (emsg_cod,emsg_msg_cod,emsg_perso_cod,emsg_archive) values (nextval('seq_emsg_cod'),$num_mes,$perso_sit,'N')";
				$db->query($req_ins_exp);
				$req_ins_dest = "insert into messages_dest (dmsg_cod,dmsg_msg_cod,dmsg_perso_cod,dmsg_lu,dmsg_archive) values (nextval('seq_dmsg_cod'),$num_mes,$perso_sitteur,'N','N')";
				$db->query($req_ins_dest);
		}
		break;
		
		case 'ancien':
		?>
		<br>La liste ci-dessous donne les sittings échus, qui ont été réalisés dans les 3 derniers mois<br>
		<br><a href="<?php echo $PHP_SELF;?>?methode=debut">Retour</a><br><hr>
		<table>
		<tr><td><b>Anciens comptes Sitteur</b></td><td><b>Date de début</b></td><td><b>Date de fin</b></td></tr>	
		<?php 
		$req = "select to_char(csit_ddeb,'DD-MM-YYYY / HH24:mi') as date_debut,to_char(csit_dfin,'DD-MM-YYYY / HH24:mi') as date_fin,csit_compte_sitteur from compte_sitting
								where csit_compte_sitte = $compt_cod
								and csit_ddeb < now()
								and csit_dfin > (now() - '3 months'::interval)
								and csit_dfin < now()
								order by csit_ddeb";
		$db->query($req);
		while ($db->next_record())
		{
				$date_deb = $db->f("date_debut");
				$date_fin = $db->f("date_fin");
				$compte_sitteur = $db->f("csit_compte_sitteur");
				$req = "select compt_nom from compte
								where compt_cod = $compte_sitteur";
				$db2->query($req);
				$db2->next_record();
				$compte_sitteur_nom = $db2->f("compt_nom");		
				?>
				<tr><td class="soustitre2"><?php echo $compte_sitteur_nom;?></td>
				<td class="soustitre2"><?php echo $date_deb;?></td>
				<td class="soustitre2"><?php echo $date_fin;?></td>
				</tr>
				<?php 		
		}
		?>
		</table>
		<?php 
		break;
		
//Partie pour le sitteur

		case 'liste_sitteur':
		?>
		<br>La liste ci-dessous donne les sittings que vous avez en cours sur un autre compte, ou qui vous ont été demandés<br>
		<br><a href="<?php echo $PHP_SELF;?>?methode=debut">Retour</a><br><hr>
		<table>
		<tr><td><b>Compte à sitter</b></td><td><b>Date de début</b></td><td><b>Date de fin</b></td><td><b>Annulation d'un sitting</b></td></tr>	
		<?php 
		$req = "select to_char(csit_ddeb,'DD-MM-YYYY / HH24:mi') as date_debut,to_char(csit_dfin,'DD-MM-YYYY / HH24:mi') as date_fin,csit_compte_sitteur,csit_compte_sitte from compte_sitting
								where csit_compte_sitteur = $compt_cod
								and csit_dfin > now()
								and csit_ddeb < now()
								order by csit_ddeb";
		$db->query($req);
		while ($db->next_record())
		{
				$date_deb = $db->f("date_debut");
				$date_fin = $db->f("date_fin");
				$compte_sitte = $db->f("csit_compte_sitte");
				$req = "select compt_nom from compte
								where compt_cod = $compte_sitte";
				$db2->query($req);
				$db2->next_record();
				$compte_sitte_nom = $db2->f("compt_nom");		
				?>
				<tr><td class="soustitre2"><?php echo $compte_sitte_nom;?></td>
				<td class="soustitre2"><?php echo $date_deb;?></td>
				<td class="soustitre2"><?php echo $date_fin;?></td>
				<td class="soustitre2"><i>Sitting en cours, il ne peut être annulé</i></td>
				</tr>
				<?php 		
		}
		$req = "select to_char(csit_ddeb,'DD-MM-YYYY / HH24:mi') as date_debut,to_char(csit_dfin,'DD-MM-YYYY / HH24:mi') as date_fin,csit_compte_sitteur,csit_cod,csit_compte_sitte,csit_compte_sitteur from compte_sitting
								where csit_compte_sitteur = $compt_cod
								and csit_dfin > now()
								and csit_ddeb > now()
								order by csit_ddeb";
		$db->query($req);
		while ($db->next_record())
		{
				$date_deb = $db->f("date_debut");
				$date_fin = $db->f("date_fin");
				$compte_sitte = $db->f("csit_compte_sitte");
				$compte_sitteur = $db->f("csit_compte_sitteur");				
				$csit_cod = $db->f("csit_cod");
				$req = "select compt_nom from compte
								where compt_cod = $compte_sitte";
				$db2->query($req);
				$db2->next_record();
				$compte_sitte_nom = $db2->f("compt_nom");		
				?>
				<tr><td class="soustitre2"><?php echo $compte_sitte_nom;?></td>
				<td class="soustitre2"><?php echo $date_deb;?></td>
				<td class="soustitre2"><?php echo $date_fin;?></td>
				<td class="soustitre2"><a href="<?php echo $PHP_SELF;?>?sit=<?php echo $csit_cod;?>&sit2=<?php echo $compte_sitte;?>&sit3=<?php echo $compte_sitteur;?>&methode=annulation_sitteur"><i>Annulation de ce sitting</i></a></td>		
				</tr>
				<?php 		
		}
		?>
		</table>
		<?php 
		break;
		
		case 'annulation_sitteur':
		if ($sit == null)
		{
				echo "Tricher n'est pas un bon concept. Merci pour la trace laissée";
		}
		else
		{
				$req = "delete from compte_sitting where csit_cod = $sit and csit_compte_sitte = $sit2 ";
				$db->query($req);
				$db->next_record();
						?>
						<br>La demande de sitting antérieure a bien été annulée. Un message a été envoyé au compte sitteur.
						<br><a href="<?php echo $PHP_SELF;?>?methode=debut">Retour</a><br><hr>
						<?php 
				//Envoi d'un message
				$req = "select pcompt_perso_cod from perso_compte where pcompt_compt_cod = $sit3 order by pcompt_perso_cod limit 1";
				$db->query($req);
				$db->next_record();
				$perso_sitteur = $db->f("pcompt_perso_cod");
				$req = "select pcompt_perso_cod from perso_compte where pcompt_compt_cod = $sit2 order by pcompt_perso_cod limit 1";
				$db->query($req);
				$db->next_record();
				$perso_sitte = $db->f("pcompt_perso_cod");
				$req = "select nextval('seq_msg_cod') as numero";
				$db->query($req);
				$db->next_record();
				$num_mes = $db->f("numero");
				//
				$corps = "La précédente demande de sitting a été annulée par votre sitteur.";
				$corps = pg_escape_string($corps);					
				$titre = "Annulation de sitting par le sitteur";
				$req_ins_mes = "insert into messages (msg_cod,msg_date2,msg_date,msg_titre,msg_corps) values ($num_mes,now(),now(),e'$titre',e'$corps') ";
				$db->query($req_ins_mes);
				/******************************/
				/* On enregistre l'expéditeur */
				/******************************/
				$req_ins_exp = "insert into messages_exp (emsg_cod,emsg_msg_cod,emsg_perso_cod,emsg_archive) values (nextval('seq_emsg_cod'),$num_mes,$perso_sitteur,'N')";
				$db->query($req_ins_exp);
				$req_ins_dest = "insert into messages_dest (dmsg_cod,dmsg_msg_cod,dmsg_perso_cod,dmsg_lu,dmsg_archive) values (nextval('seq_dmsg_cod'),$num_mes,$perso_sit,'N','N')";
				$db->query($req_ins_dest);
		}
		break;
		
		case 'ancien_sitteur':
		?>
		<br>La liste ci-dessous donne les sittings que vous avez réalisés dans les 3 derniers mois et qui sont échus<br>
		<br><a href="<?php echo $PHP_SELF;?>?methode=debut">Retour</a><br><hr>
		<table>
		<tr><td><b>Anciens comptes Sittés</b></td><td><b>Date de début</b></td><td><b>Date de fin</b></td><td><b>Durée</b> (<i>jours / heures : minutes)</i></td></tr>	
		<?php 
		$req = "select to_char(csit_ddeb,'DD-MM-YYYY / HH24:mi') as date_debut,to_char(csit_dfin,'DD-MM-YYYY / HH24:mi') as date_fin,to_char(csit_dfin-csit_ddeb,'  DD / HH24:mi') as duree,csit_compte_sitteur,csit_compte_sitte from compte_sitting
								where csit_compte_sitteur = $compt_cod
								and csit_ddeb < now()
								and csit_dfin > (now() - '3 months'::interval)
								and csit_dfin < now()
								order by csit_ddeb";
		$db->query($req);
		while ($db->next_record())
		{
				$date_deb = $db->f("date_debut");
				$date_fin = $db->f("date_fin");
				$duree = $db->f("duree");
				$compte_sitte = $db->f("csit_compte_sitte");
				$req = "select compt_nom from compte
								where compt_cod = $compte_sitte";
				$db2->query($req);
				$db2->next_record();
				$compte_sitte_nom = $db2->f("compt_nom");		
				?>
				<tr><td class="soustitre2"><?php echo $compte_sitte_nom;?></td>
				<td class="soustitre2"><?php echo $date_deb;?></td>
				<td class="soustitre2"><?php echo $date_fin;?></td>
				<td class="soustitre2"><?php echo $duree;?></td>
				</tr>
				<?php 		
		}
		?>
		</table>
		<?php 
		break;	
	}
$contenu_page = ob_get_contents();
ob_end_clean();
$t->set_var("CONTENU_COLONNE_DROITE",$contenu_page);
$t->parse('Sortie','FileRef');
$t->p('Sortie');
?>
