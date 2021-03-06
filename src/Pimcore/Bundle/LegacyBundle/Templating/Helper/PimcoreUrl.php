<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\LegacyBundle\Templating\Helper;

use Pimcore\Model\Staticroute;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class PimcoreUrl
 * decorates default pimcore_url helper and adds fallback to legacy static routes
 */
class PimcoreUrl extends \Pimcore\Templating\Helper\PimcoreUrl {

    /**
     * @param null $name
     * @param array $parameters
     * @param int $referenceType
     * @return mixed|string
     */
    public function generateUrl($name = null, $parameters = [], $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH)
    {

        try {
            return parent::generateUrl($name, $parameters, $referenceType); // TODO: Change the autogenerated stub
        } catch (\Exception $e) {

            //create route based on static routes
            $staticRoute = Staticroute::getByName($name);
            return $staticRoute->assemble($parameters);

        }

    }


}
