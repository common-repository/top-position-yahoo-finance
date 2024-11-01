<?php
/*
Plugin Name: Top Position Yahoo Finance
Plugin URI: http://www.noticiasbancarias.com/top-position-yahoo-finance/
Description: Display your favorite quotes. Chart and values. Uses Yahoo Finance API.
Author: Noticias Bancarias
Version: 0.1.0
Author URI: http://www.noticiasbancarias.com/
*/

/*
Top Position Yahoo Finance is a wordpress plugin that allows you to display chart and quotes values from Yahoo Finance.
Copyright (C) 2011 Top Position

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/
//Ddefinimos la tabla
define('TPYF_TABLE', 'tp_yahoo_finance');

// run when plugin is activated
register_activation_hook(__FILE__,'tpyf_activation');

function tpyf_activation() {//se ejecuta cuando se activa el plugin
    global $wpdb;
	$table_name = $wpdb->prefix . TPYF_TABLE;
	$tables = $wpdb->get_results("show tables;");
	$table_exists = false;
	foreach ( $tables as $table ) {
		foreach ( $table as $value ) {
			if ( $value == $table_name ) {
				$table_exists = true;
				break;
	}}}
	if ( !$	$table_exists ) {
		$wpdb->query("CREATE TABLE " . $table_name . " (tpyf_id INT(11) NOT NULL AUTO_INCREMENT, tpyf_symbol TEXT NOT NULL, tpyf_category TEXT, PRIMARY KEY ( tpyf_id ))");
		$wpdb->query("INSERT INTO " . $table_name . " (tpyf_symbol, tpyf_category) VALUES ('yes', 'credits')");
	}
}

if ( is_admin() ){	// only for administrator
	add_action('admin_menu', 'tpyf_admin_menu'); // add link to admin menu
	function tpyf_admin_menu() {
		add_options_page('Top Position Yahoo Finance', 'Top Position Yahoo Finance', 'administrator', 'top-position-yahoo-finance', 'tpyf_admin_page'); }
}

function tpyf_admin_page() {
	
	global $wpdb;
	$table_name = $wpdb->prefix . TPYF_TABLE;
	$action = !empty($_REQUEST['action']) ? $_REQUEST['action'] : '';
	$tpyf_id = !empty($_REQUEST['tpyf_id']) ? $_REQUEST['tpyf_id'] : '';
	$tpyf_symbol = !empty($_REQUEST['tpyf_symbol']) ? $_REQUEST['tpyf_symbol'] : '';
	$tpyf_category = !empty($_REQUEST['tpyf_category']) ? $_REQUEST['tpyf_category'] : '';

	switch($action) {
		case 'credits' :
			if( !empty($tpyf_symbol) ) $wpdb->query("UPDATE " . $table_name . " SET tpyf_symbol='" . $tpyf_symbol . "' WHERE tpyf_category='credits';");break;
	}

	$credits = $wpdb->get_var($wpdb->prepare("SELECT tpyf_symbol FROM " . $table_name . " WHERE tpyf_category='credits';"));


?>
<div class="wrap">
	<h2>Top Position Yahoo Finance</h2>
    <form method="post" action="<?php echo $_SERVER['PHP_SELF'] . '?page=top-position-yahoo-finance.php' ?>">
    <?php wp_nonce_field('update-options'); ?>
    <table class="widefat">
    <tbody>
    <tr>
    <th width="50" scope="row">Display Powered by link</th>
    <td width="450" align="left">
		<?php if( $credits == 'yes') { ?>
        <input size="50" name="tpyf_symbol" type="radio" id="text_powered_yes" value="yes" checked/> yes
        <input size="50" name="tpyf_symbol" type="radio" id="text_powered_no" value="no" /> no 
        <?php } else { ?>
        <input size="50" name="tpyf_symbol" type="radio" id="text_powered_yes" value="yes" /> yes
        <input size="50" name="tpyf_symbol" type="radio" id="text_powered_no" value="no" checked/> no 
        <?php } ?>
        <input type="hidden" name="action" value="credits">
        <input type="submit" value="<?php _e('Save') ?>" />
    </td>
    </tr>
    <tr>
    <td width="500" align="left" colspan="2"><p>You can display your quotes by using of the following 2 methods.<br />
      <br /><strong>1. Put <em>&lt;?php tpyf_output('your quotes coma separated') ?&gt;</em> in wordpress template.</strong></p>
      <p>For example: &lt;?php tpyf_output('^IXIC,MSFT,GOOG,AAPL') ?&gt;</p>
      <p><strong>2. Put <em>&#91;tpyf#your quotes coma separated&#93;</em> in your blog content.</strong>      </p>
      <p>For example: &#91;tpyf#^IXIC,MSFT,GOOG,AAPL&#93; </p>
      <p><br />
      </p></td>
    </tr>
    </tbody>
    </table>
    </form>
</div>

<?php } 

// adjuntamos la hoja de estilo
add_action('wp_head', 'addHeaderCode_yahoof');
function  addHeaderCode_yahoof() {
     echo '<link rel="stylesheet" type="text/css" media="all" href="' . get_bloginfo('wpurl') . '/wp-content/plugins/top-position-yahoo-finance/styles.css" />';
}

// funcion que detecta los tags especiales en el contenido
add_filter( 'the_content', 'modifyContent_yahoo' );

function  modifyContent_yahoo($content) {

	global $wpdb;
    $table_name = $wpdb->prefix . TPYF_TABLE;
	$pos = 0;
	// esta es una forma un poco cutre pendiente sustituir por pleg_replace
	$pos = strpos($content, '[tpyf#', $pos); //posicion de comienzo
	if($pos){
		$closed_pos = strpos($content, ']', $pos); //posicion de final
		if($closed_pos) {
		
		
			$diferencia = $closed_pos - $pos -5;
			$diferencia2 = $closed_pos - $pos + 2;
		
			$quotes = substr($content, ($pos + 6), $diferencia);
			$tpyf_tag =substr($content, $pos, $diferencia2);
		
			$output =  tpyf_recuperadatos($quotes);
			$content = str_replace($tpyf_tag, $output, $content);
		}
	} 
	return ($content);
}

function tpyf_recuperadatos($quotes_) {

	global $wpdb;
	$table_name = $wpdb->prefix . TPYF_TABLE;
	$credits = $wpdb->get_var($wpdb->prepare("SELECT tpyf_symbol FROM " . $table_name . " WHERE tpyf_category='credits';"));

	if(!$quotes_) $quotes_ = '^IXIC,MSFT,GOOG,AAPL';

	$quotes = explode(",", $quotes_);
	$charts = explode(",", $quotes_);
	
	$n = count($quotes);
	$comparequotes = '';
	for($i=1;$i<count($quotes);$i++) {
		$comparequotes .= $quotes[$i];
		if($i<=(count($quotes)-1)) $comparequotes .= ',';
	}
	for($i=0;$i<count($quotes);$i++) {
		$charts[$i] = 'http://chart.finance.yahoo.com/t?s='.$quotes[$i].'&amp;t=1d&width=240px&amp;nocache='.time();
	}
	
	$chart_rsc = $charts[0];
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "http://download.finance.yahoo.com/d/quotes.csv?s=".$quotes_.'&f=sl1d1t1ohgvcc6c8&e=.csv');
	curl_setopt( $ch, CURLOPT_HEADER, false );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	
	$output = curl_exec( $ch );
	
	curl_close( $ch );
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "http://download.finance.yahoo.com/d/quotes.csv?s=".$quotes_.'&f=n&e=.csv');
	curl_setopt( $ch, CURLOPT_HEADER, false );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	
	$output2 = curl_exec( $ch );
	$output2 = str_replace('"', '', $output2);
	curl_close( $ch );
	
	$lines = explode("\n", trim($output));
	$lines_n = explode("\n", trim($output2));
	
	$contenido = '
	<div class="top_ycontent_total">
	<div class="top_ycontent"><div class="h3top">Yahoo Financial Quotes</div>
	<div id="topposition">
    	<div class="topposition_chart">
    		<div class="toppositionimage"><img id="tpyimg" src="'.$chart_rsc.'" alt="Yahoo Finance Chart" class="top-chart" /></div>
        <div class="top-clear"></div>
    </div>
    <div class="topposition-quotes-f">';

	$n=0;
	foreach($lines as $line) { 
		$bc = '';
		if($n==0) $bc = "<div class='c0a94e1'></div>";
		if($n==1) $bc = "<div class='c48ae37'></div>";
		if($n==2) $bc = "<div class='cf5110c'></div>";
		if($n==3) $bc = "<div class='cc30beb'></div>";
		$contents = explode( ',', str_replace( '"', '', $line ) );
		
		$contenido .= '<div class="topposition-quotes-registro-f"><a href="javascript:void();" onclick="document.getElementById('."'tpyimg'".').src='."'".$charts[$n]."'".'" title="View chart" class="charttp"><img src="'.get_bloginfo('wpurl').'/wp-content/plugins/top-position-yahoo-finance/icon.gif"  alt="View chart" /></a><strong><a href="javascript:void();" onclick="document.getElementById('."'tpyimg'".').src='."'".$charts[$n]."'".'" title="View chart">'.$lines_n[$n].'</a></strong>: '.$contents[1] .' (<a href="http://finance.yahoo.com/q?s='.$contents[0].'" title="More on Yahoo Finance">+</a>)</div>';
		$n++;
	} 
	$contenido .= '</div><div class="top-clear"></div>';
	if($credits=='yes') $contenido .= '<div class="top-firma">By <a href="http://www.noticiasbancarias.com" title="Noticias bancarias">noticias bancarias</a></div>';
	$contenido .= '</div></div></div>';
	
	return $contenido;
}

function tpyf_output($quotes='') {
    if($quotes) echo tpyf_recuperadatos($quotes);
}
?>