<?php
/**
 * CarteBlanche - PHP framework package - MIME email bundle
 * Copyleft (c) 2013 Pierre Cassat and contributors
 * <www.ateliers-pierrot.fr> - <contact@ateliers-pierrot.fr>
 * License GPL-3.0 <http://www.opensource.org/licenses/gpl-3.0.html>
 * Sources <https://github.com/atelierspierrot/carte-blanche>
 */

namespace MimeEmail\Lib\EmailTemplate;

use \CarteBlanche\CarteBlanche;
use \MimeEmail\Lib\EmailTemplate as EmailTemplate;

/**
 */
class MailChimpEmailMarkupLayout extends EmailTemplate
{

	protected $template='simple-basic.html';

	protected $views_dir='MimeEmail/views/MailChimp/';

	protected $variables = array(
		// globals
		'MC:SUBJECT'=>'',
		'MC:CONTENT'=>'',
		
		// mailing-list info
		'LIST:COMPANY'=>'',
		'HTML:LIST_ADDRESS_HTML'=>'',
		'HTML:REWARDS'=>'',
		'LIST:DESCRIPTION'=>'',

		// utilities
		'ARCHIVE_PAGE'=>'', // if not ...
		'UNSUB'=>'', // unsubscribe
		'UPDATE_PROFILE'=>'', // user account

		// socials
		'TWITTER:PROFILEURL'=>'',
		'FACEBOOK:PROFILEURL'=>'',
		
		// automated
		'CURRENT_YEAR'=>'',
	);

	protected $parsables = array(
		'#\*\|IFNOT:([A-Z_:]+)\|\*#' => '<?php if (!strlen(\'*|$1|*\')) : ?>',
		'#\*\|IF:([A-Z_:]+)\|\*#' => '<?php if (strlen(\'*|$1|*\')) : ?>',
		'#\*\|END:IF\|\*#' => '<?php endif; ?>',
		'sprintf:#\*\|%s\|\*#' => 'sprintf:%s',
	);
	
	public function __construct()
	{
		$this->variables['CURRENT_YEAR'] = date('Y');
//		$config = CarteBlanche::getKernel()->registry->getStackEntry('globals', null, 'config');
		$config = CarteBlanche::getConfig('globals');
		if (!empty($config['author']))
			$this->variables['LIST:COMPANY'] = $config['author'];
	}

}

// Endfile