<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sylius\Bundle\RbacBundle\Twig;

use Sylius\Bundle\RbacBundle\Templating\Helper\RbacHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Sylius RBAC Twig helper.
 *
 * @author Paweł Jędrzejewski <pawel@sylius.org>
 */
class RbacExtension extends AbstractExtension
{
    /**
     * @var RbacHelper
     */
    protected $helper;

    /**
     * @param RbacHelper $helper
     */
    public function __construct(RbacHelper $helper)
    {
        $this->helper = $helper;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('sylius_is_granted', [$this, 'isGranted']),
        ];
    }

    /**
     * Check if currently logged in user is granted specific permission.
     *
     * @param string $permissionCode
     *
     * @return string
     */
    public function isGranted($permissionCode)
    {
        return $this->helper->isGranted($permissionCode);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'sylius_rbac';
    }
}
