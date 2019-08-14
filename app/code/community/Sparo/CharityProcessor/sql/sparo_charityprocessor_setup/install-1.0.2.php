<?php
$installer = $this;
$installer->startSetup();

//$installer->unregisterOldObserver();
$installer->addScriptBlock_1_0_2();
$installer->addContainerBlock();

$installer->endSetup();

?>
