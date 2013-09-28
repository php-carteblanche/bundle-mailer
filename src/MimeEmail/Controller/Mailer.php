<?php
/**
 * CarteBlanche - PHP framework package - MIME email bundle
 * Copyleft (c) 2013 Pierre Cassat and contributors
 * <www.ateliers-pierrot.fr> - <contact@ateliers-pierrot.fr>
 * License GPL-3.0 <http://www.opensource.org/licenses/gpl-3.0.html>
 * Sources <https://github.com/atelierspierrot/carte-blanche>
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