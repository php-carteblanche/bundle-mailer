<?php
/**
 * CarteBlanche - PHP framework package - MIME email bundle
 * Copyleft (c) 2013 Pierre Cassat and contributors
 * <www.ateliers-pierrot.fr> - <contact@ateliers-pierrot.fr>
 * License Apache-2.0 <http://www.apache.org/licenses/LICENSE-2.0.html>
 * Sources <http://github.com/php-carteblanche/carteblanche>
 */

namespace MimeEmail\Lib;

use \CarteBlanche\CarteBlanche;

/**
 */
class EmailTemplate
{

	protected $template=null;
	protected $views_dir='MimeEmail/views/';
	protected $variables=array();
	protected $parsables=array();

	private $content;

	public function getContent()
	{
		return $this->content;
	}

	public function setVariables( $vars )
	{
		if (!is_array($vars)) return;
		$this->variables = array_merge($this->variables, $vars);
		return $this;
	}

	public function setTemplate( $template )
	{
		$this->template = $template;
		return $this;
	}

	public function parse( $template=null, $vars=null )
	{
		if (!is_null($template)) $this->setTemplate( $template );
		if (!is_null($vars)) $this->setVariables( $vars );

		$_f = trim($this->views_dir, '/').'/'.$this->template;
		$view_file = CarteBlanche::getContainer()
		    ->get('locator')->locateView( $_f );
		if (file_exists($view_file)) {
			$buffer = file_get_contents($view_file);
			if (!empty($this->parsables)) {
				foreach($this->parsables as $mask=>$replace) {
					$on_variables 	= substr($mask, 0, strlen('sprintf:'))=='sprintf:';
					$on_values 		= substr($replace, 0, strlen('sprintf:'))=='sprintf:';
					if (true===$on_variables) {
						$mask = substr($mask, strlen('sprintf:'));
						if (true===$on_values) {
							$replace = substr($replace, strlen('sprintf:'));
						}
						foreach ($this->variables as $var=>$val) {
							$mask_item = sprintf($mask, $var);
							if (true===$on_values) {
								$replace_item = sprintf($replace, $val);
							}
//echo "<br />trying to replace '$mask_item' with '$replace_item'";
							$buffer = preg_replace( $mask_item, $replace_item, $buffer);
						}
					} else {
//echo "<br />trying to replace '$mask' with '$replace'";
						$buffer = preg_replace($mask, $replace, $buffer);
					}
				}
			}

//echo $buffer;
		}
		
		$c = new \Lib\Cache();
		$cache = $c->cacheFile( 'mailchimp.mail', $buffer );
		if (false!==$cache)
		{
			ob_start();
			include $cache;
	    	$this->content = ob_get_contents();
	  	  	ob_end_clean();
		}
		return $this->content;
	}

}

// Endfile