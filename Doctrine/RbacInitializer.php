<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sylius\Bundle\RbacBundle\Doctrine;

use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Paweł Jędrzejewski <pawel@sylius.org>
 * @author Joseph Bielawski <stloyd@gmail.com>
 */
class RbacInitializer
{
    private $permissions;
    private $permissionsHierarchy;
    private $permissionManager;
    private $permissionRepository;

    private $permissionsByCode = array(
        'root' => null
    );

    private $roles;
    private $rolesHierarchy;
    private $roleManager;
    private $roleRepository;

    public function __construct(
        array $permissions,
        array $permissionsHierarchy,
        $permissionManager,
        RepositoryInterface $permissionRepository,
        array $roles,
        array $rolesHierarchy,
        $roleManager,
        RepositoryInterface $roleRepository
    ) {
        $this->permissions = $permissions;
        $this->permissionsHierarchy = $permissionsHierarchy;
        $this->permissionManager = $permissionManager;
        $this->permissionRepository = $permissionRepository;

        $this->roles = $roles;
        $this->rolesHierarchy = $rolesHierarchy;
        $this->roleManager = $roleManager;
        $this->roleRepository = $roleRepository;
    }

    public function initialize(OutputInterface $output = null)
    {
        $this->initializePermissions($output);
        $this->initializeRoles($output);
    }

    protected function initializePermissions(OutputInterface $output = null)
    {
        if (null === $root = $this->permissionRepository->findOneBy(array('code' => 'root'))) {
            $root = $this->permissionRepository->createNew();
            $root->setCode('root');
            $root->setDescription('Root');

            $this->permissionManager->persist($root);
            $this->permissionManager->flush();
        }

        $this->permissionsByCode['root'] = $root;

        foreach ($this->permissions as $code => $description) {
            if (null === $permission = $this->permissionRepository->findOneBy(array('code' => $code))) {
                $permission = $this->permissionRepository->createNew();
                $permission->setCode($code);
                $permission->setDescription($description);
                $permission->setParent($root);

                $this->permissionManager->persist($permission);

                if ($output) {
                    $output->writeln(sprintf('Adding permission "<comment>%s</comment>". (<info>%s</info>)', $description, $code));
                }
            }

            $this->permissionsByCode[$code] = $permission;
        }

        foreach ($this->permissionsHierarchy as $code => $children) {
            foreach ($children as $childCode) {
                $this->permissionsByCode[$code]->addChild($this->permissionsByCode[$childCode]);
            }
        }

        $this->permissionManager->flush();
    }

    protected function initializeRoles(OutputInterface $output = null)
    {
        if (!isset($this->permissionsByCode['root'])) {
            return;
        }

        if (null === $root = $this->roleRepository->findOneBy(array('code' => 'root'))) {
            $root = $this->roleRepository->createNew();
            $root->setCode('root');
            $root->setName('Root');

            $root->addPermission($this->permissionsByCode['root']);

            $this->roleManager->persist($root);
            $this->roleManager->flush();
        }

        $rolesByCode = array('root' => $root);

        foreach ($this->roles as $code => $data) {
            if (null === $role = $this->roleRepository->findOneBy(array('code' => $code))) {
                $role = $this->roleRepository->createNew();
                $role->setCode($code);
                $role->setName($data['name']);
                $role->setDescription($data['description']);
                $role->setParent($root);

                foreach ($data['permissions'] as $permission) {
                    $role->addPermission($this->permissionsByCode[$permission]);
                }

                $role->setSecurityRoles($data['security_roles']);

                $this->roleManager->persist($role);

                if ($output) {
                    $output->writeln(sprintf('Adding role "<comment>%s</comment>". (<info>%s</info>)', $data['name'], $code));
                }
            }

            $rolesByCode[$code] = $role;
        }

        foreach ($this->rolesHierarchy as $code => $children) {
            foreach ($children as $childCode) {
                $rolesByCode[$code]->addChild($rolesByCode[$childCode]);
            }
        }

        $this->roleManager->flush();
    }
}
