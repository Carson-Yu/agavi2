<?php

// +---------------------------------------------------------------------------+
// | This file is part of the Agavi package.                                   |
// | Copyright (c) 2005-2011 the Agavi Project.                                |
// |                                                                           |
// | For the full copyright and license information, please view the LICENSE   |
// | file that was distributed with this source code. You can also view the    |
// | LICENSE file online at http://www.agavi.org/LICENSE.txt                   |
// |   vi: set noexpandtab:                                                    |
// |   Local Variables:                                                        |
// |   indent-tabs-mode: t                                                     |
// |   End:                                                                    |
// +---------------------------------------------------------------------------+

use Agavi\Request\RequestDataHolder;
use Agavi\Config\Config;

class Default_Widgets_FooterSuccessView extends SampleAppDefaultBaseView
{

    public function executeHtml(RequestDataHolder $rd)
    {
        // will automatically load "slot" layout for us
        $this->setupHtml($rd);
        
        $this->setAttribute('locales', $this->tm->getAvailableLocales());
        $this->setAttribute('current_locale', $this->tm->getCurrentLocaleIdentifier());
        $this->setAttribute('agavi_plug', Config::get('agavi.release'));
    }
}
