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
if (!isset($evt_start))
{
	$evt_start = 0;
}
if ($evt_start < 0)
{
	$evt_start = 0;
}
$req_evt = "select c1.compt_nom as ancien_nom, c1.compt_cod as ancien_cod, coalesce(lie1.compt_nom, '') as lie1_nom,
		c2.compt_nom as nouveau_nom, c2.compt_cod as nouveau_cod, coalesce(lie2.compt_nom, '') as lie2_nom,
		to_char(multi_date,'DD/MM/YYYY hh24:mi:ss') as date, multi_ip
	from multi_trace
	inner join compte c1 on c1.compt_cod = multi_cpt1
	left outer join compte lie1 on lie1.compt_cod = c1.compt_compte_lie
	inner join compte c2 on c2.compt_cod = multi_cpt2
	left outer join compte lie2 on lie2.compt_cod = c2.compt_compte_lie
	where c2.compt_admin = 'N' 
		and c2.compt_monstre = 'N' 
		and c1.compt_admin = 'N' 
		and c1.compt_monstre = 'N'
		and c1.compt_confiance = 'N'
		and c2.compt_confiance = 'N'
	order by multi_cod desc 
	limit 40 
	offset $evt_start"; 
$db->query($req_evt);
?>
<table cellspacing="2">
<tr>
<td colspan="7" class="titre"><p class="titre">Multi</p></td>
</tr>
<?php 
echo "<tr>";
	echo "<td class=\"soustitre3\"><p><b>Date</b></p></td>";
	echo "<td class=\"soustitre3\"><p><b>Ancien</b></p></td>";
	echo "<td class=\"soustitre3\"><p><b>Comptes liés</b></p></td>";
	echo "<td class=\"soustitre3\"><p><b>Nouveau</b></p></td>";
	echo "<td class=\"soustitre3\"><p><b>Comptes liés</b></p></td>";
	echo "<td class=\"soustitre3\"><p><b>IP</b></p></td>";
	echo "<td class=\"soustitre3\"><p><b>Hôte</b></p></td>";
echo "</tr>";
?>
<form name="visu_evt" method="post" action="multi_trace.php">
<input type="hidden" name="visu">
<?php 
while($db->next_record())
{	
	$compte1 = $db->f("ancien_cod");
	$compte2 = $db->f("nouveau_cod");
	$compte1_nom = $db->f("ancien_nom");
	$compte2_nom = $db->f("nouveau_nom");
	$compteLie1_nom = $db->f("lie1_nom");
	$compteLie2_nom = $db->f("lie2_nom");
	echo "<tr>";
	echo "<td class=\"soustitre3\"><p>" . $db->f("date") . "</p></td>";
	echo "<td class=\"soustitre3\"><p><a href=\"detail_compte.php?compte=$compte1\">$compte1_nom</A></p></td>";
	echo "<td class=\"soustitre3\"><p><b>$compteLie1_nom</b></p></td>";
	echo "<td class=\"soustitre3\"><p><a href=\"detail_compte.php?compte=$compte2\">$compte2_nom</A></p></td>";
	echo "<td class=\"soustitre3\"><p><b>$compteLie2_nom</b></p></td>";

	$ip = $db->f("multi_ip");
	echo "<td class=\"soustitre3\"><p>" . $ip . "</td>";
	echo "<td class=\"soustitre3\"><p>" . gethostbyaddr($ip) . "</p></td>";
	
	echo("</tr>");
}
?>
<tr>
<td>
</form>
<form name="evt" method="post" action="multi_trace.php">
<input type="hidden" name="evt_start">
<?php 
if ($evt_start != 0)
{
	echo("<div align=\"left\"><a href=\"javascript:document.evt.evt_start.value=$evt_start-40;document.evt.submit();\"><== Précédent</a></div>");
}
?></td><td></td> <td></td><td></td>
<?php 
echo("<td><div align=\"right\"><a href=\"javascript:document.evt.evt_start.value=$evt_start+40;document.evt.submit();\">Suivant ==></a></div></td>");
?>
</tr>
</form>
</table>
<?php 
$contenu_page = ob_get_contents();
ob_end_clean();
$t->set_var("CONTENU_COLONNE_DROITE",$contenu_page);
$t->parse('Sortie','FileRef');
$t->p('Sortie');
?>
