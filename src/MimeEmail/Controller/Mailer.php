<?php
/**
 * This file is part of the CarteBlanche PHP framework
 * (c) Pierre Cassat and contributors
 * 
 * Sources <http://github.com/php-carteblanche/bundle-mailer>
 *
 * License Apache-2.0
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MimeEmail\Controller;

use \CarteBlanche\CarteBlanche;
use \CarteBlanche\Abstracts\AbstractController;
use \MimeEmail\Lib\EmailTemplate;

/**
 * Mailer controller : get a mail or newsletter content online
 *
 * @author 		Piero Wbmstr <piwi@ateliers-pierrot.fr>
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