<?php
/**
 * CarteBlanche - PHP framework package - MIME email bundle
 * Copyleft (c) 2013 Pierre Cassat and contributors
 * <www.ateliers-pierrot.fr> - <contact@ateliers-pierrot.fr>
 * License Apache-2.0 <http://www.apache.org/licenses/LICENSE-2.0.html>
 * Sources <http://github.com/php-carteblanche/carteblanche>
 */

namespace MimeEmail\Controller;

use \CarteBlanche\CarteBlanche;
use \CarteBlanche\App\Abstracts\AbstractController;
use \MimeEmail\Lib\EmailTemplate;

/**
 * Mailer controller : get a mail or newsletter content online
 *
 * @author 		Piero Wbmstr <piero.wbmstr@gmail.com>
 */
class Mailer extends AbstractController
{

	/**
	 */
	static $template = 'empty.htm';

	/**
	 */
	public function indexAction()
	{
		CarteBlanche::getContainer()->get('router')->redirect();
	}

	public function mailChimpAction()
	{
echo 'YO';
	}

}

// Endfile