<?php
/**
 * Configuration for Custom Store
 *
 * @category Sample_Data
 * @package  Skukla_CustomBrandSetup
 * @author   Steve Kukla <kukla@adobe.com>
 * @license  See COPYING.txt for license details.
 * @link     http://magento.com
 * Copyright © 2018 Magento. All rights reserved.
 */
namespace Skukla\CustomBrandSetup\Setup;

use Magento\Framework\Setup;
use Magento\Store\Api\GroupRepositoryInterface;
use Magento\Store\Api\StoreRepositoryInterface;

/**
 * InstallData Class
 *
 * @category Sample_Data
 * @package  Skukla_CustomBrandSetup
 * @author   Steve Kukla <kukla@adobe.com>
 * @license  See COPYING.txt for license details.
 * @link     http://magento.com
 * Copyright © 2018 Magento. All rights reserved.
 */
class InstallData implements Setup\InstallDataInterface
{
    /**
     * Store View
     *
     * @var \Magento\Store\Api\Data\StoreInterfaceFactory
     */
    private $_storeView;

    /**
     * @var \Magento\Store\Api\StoreRepositoryInterfaceFactory
     */
    private $storeRepositoryFactory;

    /**
     * Website Factory
     *
     * @var \Magento\Store\Api\Data\WebsiteInterfaceFactory
     */
    private $_websiteFactory;

    /**
     * Group Factory
     *
     * @var \Magento\Store\Api\Data\GroupInterfaceFactory
     */
    private $_groupFactory;

    /**
     * Group Resource
     *
     * @var \Magento\Store\Model\ResourceModel\Group
     */
    private $_groupResourceModel;

    /**
     * Category Factory
     *
     * @var \Magento\Catalog\Api\Data\CategoryInterfaceFactory
     */
    private $_categoryFactory;

    /**
     * Area Code
     *
     * @var \Magento\Framework\App\State
     */
    private $_state;

    /**
     * Resource Configuration
     *
     * @var \Magento\Config\Model\ResourceModel\Config
     */
    private $resourceConfig;

    /**
     * Theme Collection
     *
     * @var \Magento\Theme\Model\ResourceModel\Theme\Collection
     */
    private $themeCollection;

    /**
     * Theme registration
     *
     * @var \Magento\Theme\Model\Theme\Registration
     */
    private $themeRegistration;

    /**
     * Sales setup factory
     *
     * @var \Magento\SalesSequence\Model\EntityPool
     */
    private $entityPool;

    /**
     * @var \Magento\SalesSequence\Model\Builder
     */
    private $sequenceBuilder;

    /**
     * @var \Magento\SalesSequence\Model\Config
     */
    private $sequenceConfig;

    /**
     * InstallData constructor.
     * @param \Magento\Store\Api\Data\StoreInterfaceFactory $_storeView
     * @param \Magento\Store\Api\StoreRepositoryInterfaceFactory $storeRepositoryFactory
     * @param \Magento\Store\Api\Data\WebsiteInterfaceFactory $_websiteFactory
     * @param \Magento\Store\Api\Data\GroupInterfaceFactory $_groupFactory
     * @param \Magento\Store\Api\GroupRepositoryInterfaceFactory $groupRepositoryFactory
     * @param \Magento\Store\Model\ResourceModel\Group $_groupResourceModel
     * @param \Magento\Catalog\Api\Data\CategoryInterfaceFactory $_categoryFactory
     * @param \Magento\Framework\App\State $_state
     * @param \Magento\Config\Model\ResourceModel\Config $resourceConfig
     * @param \Magento\Theme\Model\ResourceModel\Theme\Collection $themeCollection
     * @param \Magento\Theme\Model\Theme\Registration $themeRegistration
     * @param \Magento\SalesSequence\Model\EntityPool $entityPool
     * @param \Magento\SalesSequence\Model\Builder $sequenceBuilder
     * @param \Magento\SalesSequence\Model\Config $sequenceConfig
     */
    public function __construct(
        \Magento\Store\Api\Data\StoreInterfaceFactory $_storeView,
        \Magento\Store\Api\StoreRepositoryInterfaceFactory $storeRepositoryFactory,
        \Magento\Store\Api\Data\WebsiteInterfaceFactory $_websiteFactory,
        \Magento\Store\Api\Data\GroupInterfaceFactory $_groupFactory,
        \Magento\Store\Api\GroupRepositoryInterfaceFactory $groupRepositoryFactory,
        \Magento\Store\Model\ResourceModel\Group $_groupResourceModel,
        \Magento\Catalog\Api\Data\CategoryInterfaceFactory $_categoryFactory,
        \Magento\Framework\App\State $_state,
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        \Magento\Theme\Model\ResourceModel\Theme\Collection $themeCollection,
        \Magento\Theme\Model\Theme\Registration $themeRegistration,
        \Magento\SalesSequence\Model\EntityPool $entityPool,
        \Magento\SalesSequence\Model\Builder $sequenceBuilder,
        \Magento\SalesSequence\Model\Config $sequenceConfig
    ) {
    
        $this->storeView = $_storeView;
        $this->storeRepositoryFactory = $storeRepositoryFactory;
        $this->websiteFactory = $_websiteFactory;
        $this->groupFactory = $_groupFactory;
        $this->groupRepositoryFactory = $groupRepositoryFactory;
        $this->groupResourceModel = $_groupResourceModel;
        $this->categoryFactory = $_categoryFactory;
        $this->config = include 'Config.php';
        $this->_resourceConfig = $resourceConfig;
        $this->themeCollection = $themeCollection;
        $this->themeRegistration = $themeRegistration;
        $this->entityPool = $entityPool;
        $this->sequenceBuilder = $sequenceBuilder;
        $this->sequenceConfig = $sequenceConfig;
        try{
            $_state->setAreaCode('adminhtml');
        }
        catch(\Magento\Framework\Exception\LocalizedException $e){
            // left empty
        }
    }

    /**
     * Install - Create Root Catalog, Group, View
     *
     * @param Setup\ModuleDataSetupInterface $setup         Setup
     * @param Setup\ModuleContextInterface   $moduleContext Module Context
     * 
     * @throws \Magento\Framework\Exception\LocalizedException
     *
     * @return null
     */
    public function install(Setup\ModuleDataSetupInterface $setup,
        Setup\ModuleContextInterface $moduleContext
    ) {
    
        // Create Root Catalog
        $rootCategoryId = $this->createCategory();


        // Get Website
        $website = $this->websiteFactory->create();
        $website->load($this->config['website']);

        // Create Custom Store Group / Store
        if ($website->getId()) {

            $group = $this->groupFactory->create();
            //Check if the store group exists. if it does, load and update
            $existingGroupId = $this->getExistingGroupId($this->config['newGroupCode']);
            if($existingGroupId !=0) {
                $group->load($existingGroupId);
            }
            $group->setWebsiteId($website->getWebsiteId());
            $group->setName($this->config['groupName']);
            $group->setRootCategoryId($rootCategoryId);
            $group->setCode($this->config['newGroupCode']);
            $this->groupResourceModel->save($group);


            // Create Store view
            $newStore = $this->storeView->create();
            // Check if the store view exists, if it does load and update
            $existingStoreId = $this->getExistingStoreId($this->config['newViewCode']);
            if($existingStoreId !=0){
                $newStore->load($existingStoreId);
            }
            $newStore->setName($this->config['newViewName']);
            $newStore->setCode($this->config['newViewCode']);
            $newStore->setWebsiteId($website->getId());
            // GroupId is a Store ID (in adminhtml terms)
            $newStore->setGroupId($group->getId());
            $newStore->setSortOrder($this->config['newViewPriority']);
            $newStore->setIsActive(false); // Set the store view to inactive by default
            $newStore->save();
            // Assign the view as the default on the Custom store
            $group->setDefaultStoreId($newStore->getId());
            $group->save();

            //add sequences
            foreach ($this->entityPool->getEntities() as $entityType) {
                $this->sequenceBuilder->setPrefix($this->sequenceConfig->get('prefix'))
                    ->setSuffix($this->sequenceConfig->get('suffix'))
                    ->setStartValue($this->sequenceConfig->get('startValue'))
                    ->setStoreId($newStore->getId())
                    ->setStep($this->sequenceConfig->get('step'))
                    ->setWarningValue($this->sequenceConfig->get('warningValue'))
                    ->setMaxValue($this->sequenceConfig->get('maxValue'))
                    ->setEntityType($entityType)->create();
            }

            // Make sure theme is registered
            $this->themeRegistration->register();
            $themeId = $this->themeCollection->getThemeByFullPath('frontend/Custom/blank')->getThemeId();
            // Set Theme for Custom Store
            $this->_resourceConfig->saveConfig("design/theme/theme_id", $themeId, "stores", $newStore->getId());
            // Set Custom name used in configuration
            $this->_resourceConfig->saveConfig("general/store_information/name", 
                $this->config['customName'], "stores", $newStore->getId());
            // Set Custom description used by store switcher
            $this->_resourceConfig->saveConfig("general/store_information/description", 
                $this->config['customDescription'], "stores", $newStore->getId());
        } else {
            throw new \Magento\Framework\Exception\LocalizedException(__("Default website does not exist."));

        }

    }

    /**
     * Creates Category and returns new category id
     *
     * @return int|null
     */
    protected function createCategory()
    {
          $data = [
                'parent_id' => 1,
                'name' => $this->config['rootCategoryName'],
                'is_active' => 1,
                'is_anchor' => 1,
                'include_in_menu' => 0,
                'position'=>10,
                'store_id'=>0
            ];
            $category = $this->categoryFactory->create();
            $foo=$category->getDefaultAttributeSetId();
            $category->setData($data)
                ->setPath('1')
                ->setAttributeSetId($category->getDefaultAttributeSetId());
            $category->save();
            return $category->getId();

    }

    /**
     * @param $groupCode string
     * @return int
     */
    public function getExistingGroupId($groupCode){
        $groupRepository = $this->groupRepositoryFactory->create();
        $groups=$groupRepository->getList();
        foreach($groups as $group){
            if($group->getCode()==$groupCode){
                return $group->getId();
                break;
            }
        }
        return 0;
    }

    /**
     * @param $storeCode string
     * @return int
     */
    public function getExistingStoreId($storeCode){
        $storeRepository = $this->storeRepositoryFactory->create();
        $stores=$storeRepository->getList();
        foreach($stores as $store){
            if($store->getCode()==$storeCode){
                return $store->getId();
                break;
            }
        }
        return 0;
    }
}
