<?php

namespace Julicraft_44\LobbySystem;

use pocketmine\Player;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\inventory\InventoryPickupArrowEvent;
use pocketmine\event\inventory\InventoryPickupItemEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\item\ItemFactory;
use pocketmine\level\Position;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\Item;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use onebone\economyapi\EconomyAPI;
use libpmquery\PMQuery;
use libpmquery\PmQueryException;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\player\cheat\PlayerCheatEvent;

class Main extends PluginBase implements Listener { 
    
    public $economy;
    public $gadgetConfigSpeed;
    public $gadgetConfigJumpBoost;
    public $gadgetConfigNausea;
    public $gadgetConfigInvisibility;
    public $gadgetConfigNightvision;
       
    public function onEnable() {
        $this->saveDefaultConfig();
        @mkdir($this->getDataFolder());
        $this->saveResource("GadgetItems.yml");
        $this->gadgetConfigSpeed = new Config($this->getDataFolder() . "GadgetItemSpeed.yml", Config::YAML);
        $this->gadgetConfigJumpBoost = new Config($this->getDataFolder() . "GadgetItemJumpBoost.yml", Config::YAML);
        $this->gadgetConfigNausea = new Config($this->getDataFolder() . "GadgetItemNausea.yml", Config::YAML);
        $this->gadgetConfigInvisibility = new Config($this->getDataFolder() . "GadgetItemInvisibility.yml", Config::YAML);
        $this->gadgetConfigNightvision = new Config($this->getDataFolder() . "GadgetItemNightvision.yml", Config::YAML);
		
		$this->LobbyWorlds = $this->getConfig()->get("LobbyWorlds");
        
        $this->economy = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI");
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        
        $this->db = new \SQLite3($this->getDataFolder() . "LobbyWarps.db");
        $this->db->exec("CREATE TABLE IF NOT EXISTS LobbyWarps(warpname TEXT PRIMARY KEY, x INT, y INT, z INT, world TEXT, image TEXT);");
        
        if($this->getConfig()->get("version") !== 1.0) {
            $this->getLogger()->critical("The version of the config is outdated.");
        }
    }
    
    public function onCommand(CommandSender $sender,Command $cmd, string $label, array $args): bool {
        switch($cmd->getName()) {
            case "day":
                if($sender instanceof Player) {
                    if ($sender->hasPermission("ls.day")) {
                        $sender->getLevel()->setTime(1000);
                        $sender->sendMessage($this->getConfig()->get("time-day"));
                    }
                }
                break;
            case "night":
                if ($sender instanceof Player) {
                    if ($sender->hasPermission("ls.night")) {
                        $sender->getLevel()->setTime(13000);
                        $sender->sendMessage($this->getConfig()->get("time-night"));
                    }
                }
                break;
            case "getlobbyinv":
                if ($sender instanceof Player) {
                    if ($sender->hasPermission("ls.getlobbyinv")) {
                        $sender->sendMessage($this->getConfig()->get("get-lobby-inv"));
                        
                        $sender->getPlayer()->getInventory()->clearAll();
                        $sender->getPlayer()->getArmorInventory()->clearAll();
                        
                        $item1 = ItemFactory::get(345, 0 , 1);
                        $item1->setCustomName($this->getConfig()->get("CompassName"));
                        $sender->getPlayer()->getInventory()->setItem(4, $item1);
                        
                        $item2 = ItemFactory::get(160, 14, 1);
                        $item2->setCustomName($this->getConfig()->get("TorchNameHide"));
                        $sender->getPlayer()->getInventory()->setItem(2, $item2);
                        
                        $item3 = ItemFactory::get(54, 0, 1);
                        $item3->setCustomName($this->getConfig()->get("ChestName"));
                        $sender->getPlayer()->getInventory()->setItem(6, $item3);
                        
                        $item4 = ItemFactory::get(397, 3, 1);
                        $item4->setCustomName($this->getConfig()->get("SkullName"));
                        $sender->getPlayer()->getInventory()->setItem(8, $item4);
                        
                        $item5 = ItemFactory::get(166, 0, 1);
                        $item5->setCustomName($this->getConfig()->get("BarrierName"));
                        $sender->getPlayer()->getInventory()->setItem(0, $item5);
                    }
                }
                break;
            case "build":
                if ($sender instanceof Player) {
                    if ($sender->hasPermission("ls.build")) {
                        $sender->getPlayer()->getInventory()->clearAll();
                        $sender->getPlayer()->getArmorInventory()->clearAll();
                    }
                }
                break;
            #case "addlsw":
            #    if ($sender instanceof Player) {
            #        if ($sender->hasPermission("ls.addlsw")) {
            #            if (empty($args[0])) {
            #                $sender->sendMessage($this->getConfig()->get("empty-add-warp"));
            #                return true;
            #           }
            #            $warpname = $args[0];
            #            if (empty($args[1])) {
            #                $image = "NoImage";
            #            } else {
            #                $image = $args[1];
            #            }
            #            $lobbywarp = $this->db->query("SELECT * FROM LobbyWarps WHERE warpname = '$warpname';");
            #            $array = $lobbywarp->fetchArray(SQLITE3_ASSOC);
            #            if (!empty($array)) {
            #                $sender->sendMessage($this->getConfig()->get("warp-exist"));
            #                return true;
            #            }
            #            $warpdb = $this->db->prepare("INSERT OR REPLACE INTO LobbyWarps(warpname, x, y, z, world, image) VALUES (:warpname, :x, :y, :z, :world, :image);");
            #            $warpdb->bindValue(":warpname", $warpname);
            #            $warpdb->bindValue(":x", $sender->getX());
            #            $warpdb->bindValue(":y", $sender->getY());
            #            $warpdb->bindValue(":z", $sender->getZ());
            #            $warpdb->bindValue(":world", $sender->getPlayer()->getLevel()->getName());
            #            $warpdb->bindValue(":image", $image);
            #            $result = $warpdb->execute();
            #            $sender->sendMessage($this->getConfig()->get("success-warp-add"));
            #            
            #        } else {
            #            $sender->sendMessage($this->getConfig()->get("no-permission"));
            #        }
            #    } else {
            #        $sender->sendMessage($this->getConfig()->get("no-player"));
            #    }
            #    break;
            #case "dellsw":
            #    if ($sender instanceof Player) {
            #        if ($sender->hasPermission("ls.dellsw")) {
            #            if(empty($args)) {
            #                $sender->sendMessage($this->getConfig()->get("empty-remove-warp"));
            #                return true;
            #            }
            #            $warpname = $args[0];
            #            $lobbywarp = $this->db->query("SELECT * FROM LobbyWarps WHERE warpname = '$warpname';");
            #            $array = $lobbywarp->fetchArray(SQLITE3_ASSOC);
            #            if (!empty($array)) {
            #                $this->db->query("DELETE FROM LobbyWarps WHERE warpname = '$warpname';");
            #               $sender->sendMessage($this->getConfig()->get("success-warp-remove"));
            #            } else {
            #                $sender->sendMessage($this->getConfig()->get("warp-not-exist"));
            #            }
            #        } else {
            #            $sender->sendMessage($this->getConfig()->get("no-permission"));
            #        }
            #    } else {
            #        $sender->sendMessage($this->getConfig()->get("no-player"));
            #    }
            #    break;
        }
        return true;
    }
    
   # public function createCompassForm($player) {
       # $form = $this->getServer()->getPluginManager()->getPlugin("FormAPI")->createSimpleForm(function (Player $player, $data) {
          #  $result = $data[0];
           # if ($result === null) {
               # return true;
           # }
            #$lobbywarp = $this->db->query("SELECT * FROM LobbyWarps;");
            #$i = -1;
            #while ($resultArr = $lobbywarp->fetchArray(SQLITE3_ASSOC)) {
                #$j = $i +1;
                #$warpname = $resultArr['warpname'];
                #$i = $i + 1;
                #if ($result == $j) {
                    #$lobbywarp = $this->db->query("SELECT * FROM LobbyWarps HERE warpname = '$warpname';");
                    #$array = $lobbywarp->fetchArray(SQLITE3_ASSOC);
                    #if (!empty($array)) {
                        #$player->getPlayer()->teleport(new Position($array['x'], $array['y'], $array['z'], $this->getServer()->getLevelByName($array['world'])));
                        #$player->sendMessage($this->getConfig()->get("success-lobby-teleport"));
                    #}
                #}
            #}
      # });
        #$form->setTitle($this->getConfig()->get("CompassName"));
        #$form->setContent($this->getConfig()->get("CompassContent")); 
        #$result = $this->db->query("SELECT * FROM lobbywarps;");
        #$i = -1;
        #while ($resultArr = $result->fetchArray(SQLITE3_ASSOC)) {
            #$j = $i + 1;
            #$warpname = $resultArr['warpname'];
            #$form->addButton(TextFormat::GOLD . "$warpname");
            #$i = $i + 1;
      #  }
        #$form->sendToPlayer($player);               
#}

    public function createCompassForm($player) {
        $form = $this->getServer()->getPluginManager()->getPlugin("FormAPI")->createSimpleForm(function (Player $player, $data) {
            $result = $data;
            if ($result === null) {
                return true;
            }
            switch ($result) {
                case 0:
                    $player->transfer("astrofight.mc-play.net", 19131, "TransferDueLobbyPlugin - MLGRush");
                    break;
                case 1:
                    $player->transfer("astrofight.mc-play.net", 19130, "TransferDueLobbyPlugin - KnockFFA");
                    break;
                case 2:
                    $player->transfer("astrofight.mc-play.net", 19133, "TransferDueLobbyPlugin - MurderMystery");
                    break;
				case 3:
					$player->transfer("astrofight.mc-play.net", 19134, "TransferDueLobbyPlugin - SkyBlock");
            }
        });
            try {
                $mlgrush = PMQuery::query("astrofight.mc-play.net", 19131);
                $mlgrushplayers = $mlgrush['Players'];
                $mlgrushmaxplayers = $mlgrush['MaxPlayers'];
                $form->addButton(TextFormat::DARK_RED . "MlgRush" . TextFormat::DARK_GRAY . " (" . TextFormat::GRAY . $mlgrushplayers . TextFormat::DARK_GRAY . "/" . TextFormat::GRAY . $mlgrushmaxplayers . TextFormat::DARK_GRAY . ")");
            } catch (PmQueryException $e) {
                $player->sendMessage("§cThe MLGRush Server is offline and can't showing up. Please contact a developer!");
                }
            try {
                $knockback = PMQuery::query("astrofight.mc-play.net", 19130);
                $knockbackplayers = $knockback['Players'];
                $knockbackmaxplayers = $knockback['MaxPlayers'];
                $form->addButton(TextFormat::DARK_RED . "KnockbackFFA" . TextFormat::DARK_GRAY . " (" . TextFormat::GRAY . $knockbackplayers . TextFormat::DARK_GRAY . "/" . TextFormat::GRAY . $knockbackmaxplayers . TextFormat::DARK_GRAY . ")");
            } catch (PmQueryException $e) {
                $player->sendMessage("§cThe KnockbackFFA Server is offline and can't showing up. Please contact a developer!");
                }
            try {
                $mm = PMQuery::query("astrofight.mc-play.net", 19133);
                $mmplayers = $mm['Players'];
                $mmmaxplayers = $mm['MaxPlayers'];
                $form->addButton(TextFormat::DARK_RED . "MurderMystery" . TextFormat::DARK_GRAY . " (" . TextFormat::GRAY . $mmplayers . TextFormat::DARK_GRAY . "/" . TextFormat::GRAY . $mmmaxplayers . TextFormat::DARK_GRAY . ")");
            } catch(PmQueryException $e) {
                $player->sendMessage("§cThe MurderMystery Server is offline and can't showing up. Please contact a developer!");   
                }
			try {
				$sb = PmQuery::query("astrofight.mc-play.net", 19134);
				$sbplayers = $sb['Players'];
				$sbmaxplayers = $sb['MaxPlayers'];
				$form->addButton(TextFormat::DARK_RED . "SkyBlock" . TextFormat::DARK_GRAY . " (" . TextFormat::GRAY . $sbplayers . TextFormat::DARK_GRAY . "/" . TextFormat::GRAY . $sbmaxplayers . TextFormat::DARK_GRAY . ")");
			} catch(PmQueryException $e) {
				$player->sendMessage("§cThe SkyBlock Server is offline and can't showing up. Please contact a developer!");
			}
				
            $form->setTitle($this->getConfig()->get("CompassName"));
            $form->setContent($this->getConfig()->get("CompassContent"));   
            $form->sendToPlayer($player);

    }
   
    public function createChestForm($player) {
        $form = $this->getServer()->getPluginManager()->getPlugin("FormAPI")->createSimpleForm(function (Player $player, $data) {
            $result = $data;
            if ($result === null) {
                return true;
            }
            switch ($result) {
                case 0:
                    $this->createEffectForm($player);
                    break;
                case 1:                
                    $player->sendMessage("In Arbeit");
                    break;
                case 2:
                    $player->removeAllEffects();
                    $player->sendMessage("§l§cAstro§aFight§r§7| §rAlle Effekte entfernt");
                    break;
            }
    });
            $form->setTitle($this->getConfig()->get("CompassName"));
            $form->setContent($this->getConfig()->get("ChestContent"));
            $form->addButton("Effekte");
            $form->addButton("Laufpartikel");
            $form->addButton("Alles entfernen");
            $form->sendToPlayer($player);
   }
   
    public function createEffectForm($player) {
        $form = $this->getServer()->getPluginManager()->getPlugin("FormAPI")->createSimpleForm(function (Player $player, $data) {
            $name =  $player->getName();
            $result = $data;
            if ($result === null) {
                return true;
            }
            switch ($result) {
                case 0:  //speed
                    if($this->gadgetConfigSpeed->exists($name)) {
                         $speed = new EffectInstance(Effect::getEffect(Effect::SPEED), 99999, 2);
                         $player->addEffect($speed);
                    } else {
                         if(EconomyAPI::getInstance()->myMoney($name) >= 900) {
                            EconomyAPI::getInstance()->reduceMoney($name, 900);
                            $speed = new EffectInstance(Effect::getEffect(Effect::SPEED), 99999, 2);
                            $player->addEffect($speed);
                            $this->gadgetConfigSpeed->set($name, true);
                            $this->gadgetConfigSpeed->save(true);
                         } else {
                            $player->sendMessage("§l§cAstro§aFight§r§7| §rNicht genug Geld");   
                         }
                         }
                    break;
                case 1: //Jump Boost
                    if($this->gadgetConfigJumpBoost->exists($name)) {
                        $speed = new EffectInstance(Effect::getEffect(Effect::JUMP_BOOST), 99999, 2);
                        $player->addEffect($speed);
                    } else {
                        if(EconomyAPI::getInstance()->myMoney($name) >= 900) {
                            EconomyAPI::getInstance()->reduceMoney($name, 900);
                            $speed = new EffectInstance(Effect::getEffect(Effect::JUMP_BOOST), 99999, 2);
                            $player->addEffect($speed);
                            $this->gadgetConfigJumpBoost->set($name, true);
                            $this->gadgetConfigJumpBoost->save(true);
                        } else {
                            $player->sendMessage("§l§cAstro§aFight§r§7| §rNicht genug Geld");
                        }
                    }
                    break;
                case 2:
                    if($this->gadgetConfigNausea->exists($name)) {
                        $speed = new EffectInstance(Effect::getEffect(Effect::NAUSEA), 99999, 2);
                        $player->addEffect($speed);
                    } else {
                        if(EconomyAPI::getInstance()->myMoney($name) >= 700) {
                            EconomyAPI::getInstance()->reduceMoney($name, 700);
                            $speed = new EffectInstance(Effect::getEffect(Effect::NAUSEA), 99999, 2);
                            $player->addEffect($speed);
                            $this->gadgetConfigNausea->set($name, true);
                            $this->gadgetConfigNausea->save(true);
                        } else {
                            $player->sendMessage("§l§cAstro§aFight§r§7| §rNicht genug Geld");
                        }
                    }
                    break;
                case 3:
                    if($this->gadgetConfigInvisibility->exists($name)) {
                        $speed = new EffectInstance(Effect::getEffect(Effect::INVISIBILITY), 99999, 2);
                        $player->addEffect($speed);
                    } else {
                        if(EconomyAPI::getInstance()->myMoney($name) >= 2500) {
                            EconomyAPI::getInstance()->reduceMoney($name, 2500);
                            $speed = new EffectInstance(Effect::getEffect(Effect::INVISIBILITY), 99999, 2);
                            $player->addEffect($speed);
                            $this->gadgetConfigInvisibility->set($name, true);
                            $this->gadgetConfigInvisibility->save(true);
                        } else {
                            $player->sendMessage("§l§cAstro§aFight§r§7| §rNicht genug Geld");
                        }
                    }
                    break;
                case 4:
                    if($this->gadgetConfigNightvision->exists($name)) {
                        $speed = new EffectInstance(Effect::getEffect(Effect::NIGHT_VISION), 99999, 2);
                        $player->addEffect($speed);
                    } else {
                        if(EconomyAPI::getInstance()->myMoney($name) >= 1000) {
                            EconomyAPI::getInstance()->reduceMoney($name, 1000);
                            $speed = new EffectInstance(Effect::getEffect(Effect::NIGHT_VISION), 99999, 2);
                            $player->addEffect($speed);
                            $this->gadgetConfigNightvision->set($name, true);
                            $this->gadgetConfigNightvision->save(true);
                        } else {
                            $player->sendMessage("§l§cAstro§aFight§r§7| §rNicht genug Geld");
                        }
                    }
                    break;
                    
                 }
    });
           $form->setTitle($this->getConfig()->get("CompassName"));
           $form->setContent($this->getConfig()->get("EffectContent"));
           $form->addButton(TextFormat::AQUA . "Schnelligkeit");
           $form->addButton(TextFormat::AQUA . "Sprungkraft");
           $form->addButton(TextFormat::AQUA . "Uebelkeit");
           $form->addButton(TextFormat::AQUA . "Unsichtbarkeit");
           $form->addButton(TextFormat::AQUA . "Nachtsicht");
           $form->sendToPlayer($player);
   }
   
   public function createProfileForm($player) {
       $form = $this->getServer()->getPluginManager()->getPlugin("FormAPI")->createSimpleForm(function (Player $player, $data) {
          $result = $data; 
          if ($result === null) {
              return true;
          }
          switch ($result) {
              case 0:
                  //...
                  break;
          }
       });
            $form->setTitle($this->getConfig()->get("SkullName"));
            $form->setContent($this->getConfig()->get("SkullContent"));
            $form->addButton("Test");
            $form->sendToPlayer($player);
   }
    
    public function onJoin(PlayerJoinEvent $e) {
		if(in_array($e->getPlayer()->getLevel()->getName(), $this->LobbyWorlds)) {
			
			$e->getPlayer()->setXpLevel(2021);
			$e->setJoinMessage("");
			foreach ($this->getServer()->getOnlinePlayers() as $p) {
				$p->sendPopup(TextFormat::GRAY . "[" . TextFormat::GREEN . "+" . TextFormat::GRAY . "] " . $e->getPlayer()->getName());
			}
        
			$e->getPlayer()->getInventory()->clearAll();
			$e->getPlayer()->getArmorInventory()->clearAll();
			$e->getPlayer()->removeAllEffects();
        
			$item1 = ItemFactory::get(345, 0 , 1);
			$item1->setCustomName($this->getConfig()->get("CompassName"));
			$e->getPlayer()->getInventory()->setItem(4, $item1);
        
			$item2 = ItemFactory::get(160, 14, 1);
			$item2->setCustomName($this->getConfig()->get("TorchNameHide"));
			$e->getPlayer()->getInventory()->setItem(2, $item2);    
        
			$item3 = ItemFactory::get(54, 0, 1);
			$item3->setCustomName($this->getConfig()->get("ChestName"));
			$e->getPlayer()->getInventory()->setItem(6, $item3);
        
			$item4 = ItemFactory::get(397, 3, 1);
			$item4->setCustomName($this->getConfig()->get("SkullName"));
			$e->getPlayer()->getInventory()->setItem(8, $item4);
        
			$item5 = ItemFactory::get(166, 0, 1);
			$item5->setCustomName($this->getConfig()->get("BarrierName"));
			$e->getPlayer()->getInventory()->setItem(0, $item5);
		}
    }
    
    public function onLeave(PlayerQuitEvent $e) {
        $e->setQuitMessage("");
        foreach ($this->getServer()->getOnlinePlayers() as $p) {
            $p->sendPopup(TextFormat::GRAY . "[" . TextFormat::RED . "-" . TextFormat::GRAY . "] " . $e->getPlayer()->getName());
        }
        
    }
    
    public function onInteract(PlayerInteractEvent $e) {
		if(in_array($e->getPlayer()->getLevel()->getName(), $this->LobbyWorlds)) {
			$player = $e->getPlayer();
			$item = $e->getItem();
			#Compass Interaction
			if($item->getId() == 345) {
				$this->createCompassForm($player);
			}
			#Player Hide - Interaction
			if($item->getCustomName() == $this->getConfig()->get("TorchNameHide")) {
				$player->sendMessage($this->getConfig()->get("hide-player"));
            
				$item2 = ItemFactory::get(160, 5, 1);
				$item2->setCustomName($this->getConfig()->get("TorchNameShow"));
				$e->getPlayer()->getInventory()->setItem(2, $item2);
            
					foreach($this->getServer()->getOnlinePlayers() as $players) {
						$player->hidePlayer($players);
					}
			}
			#Player Show - Interaction
			if($item->getCustomName() == $this->getConfig()->get("TorchNameShow")) {
				$player->sendMessage($this->getConfig()->get("show-player"));
            
				$item2 = ItemFactory::get(160, 14, 1);
				$item2->setCustomName($this->getConfig()->get("TorchNameHide"));
				$e->getPlayer()->getInventory()->setItem(2, $item2);  
            
				foreach($this->getServer()->getOnlinePlayers() as $players) {
					$player->showPlayer($players);
				}
			}
			#Chest Interaction
			if($item->getCustomName() == $this->getConfig()->get("ChestName")) {
				$this->createChestForm($player);
			}
			#Skull Interaction
			if($item->getCustomName() == $this->getConfig()->get("SkullName")) {
				$this->createProfileForm($player);
			}
		}
    }
    
    public function onDrop(PlayerDropItemEvent $e) {
		if(in_array($e->getPlayer()->getLevel()->getName(), $this->LobbyWorlds)) {
			if ($this->getConfig()->get("canDropItem") == false) {
				$e->setCancelled(true);
			}
		}
    }
    
    public function onPickupItem(InventoryPickupItemEvent $e) {
		if(in_array($e->getItem()->getLevel()->getName(), $this->LobbyWorlds)) {
			if ($this->getConfig()->get("canPickupItem") == false) {
				$e->setCancelled(true);
			}
        }
    }
    
    public function onPickupArrow(InventoryPickupArrowEvent $e) {
		if(in_array($e->getPlayer()->getLevel()->getName(), $this->LobbyWorlds)) {
			if ($this->getConfig()->get("canPickupItem") == false) {
				$e->setCancelled(true);
			}
        }
    }
    
    public function dropItemOnDeath(PlayerDeathEvent $e) {
		if(in_array($e->getPlayer()->getLevel()->getName(), $this->LobbyWorlds)) {
			if ($this->getConfig()->get("canDropItem") == false) {
				$e->setDrops([]);
				$e->setCancelled(true);
			}
			$e->setDeathMessage("");
		}
    }
    
    public function onHungerLose(PlayerExhaustEvent $e) {
		if(in_array($e->getPlayer()->getLevel()->getName(), $this->LobbyWorlds)) {
			if ($this->getConfig()->get("canLoseHunger") == false) {
				$e->setCancelled(true);
			}
        }
    }
    
    public function onBreak(BlockBreakEvent $e) {
		if(in_array($e->getPlayer()->getLevel()->getName(), $this->LobbyWorlds)) {
			if ($this->getConfig()->get("canBreakBlock") == false) {
				$e->setCancelled(true);
			}
        }
    }
    
    public function onPlace(BlockPlaceEvent $e) {
		if(in_array($e->getPlayer()->getLevel()->getName(), $this->LobbyWorlds)) {
			if ($this->getConfig()->get("canPlaceBlock") == false) {
				$e->setCancelled(true);
			}
        }
    }
    
   public function onDamage(EntityDamageEvent $e) {
        
        #Check if player is in world
		if ($this->getConfig()->get("canTakeDamage") == false) {
			if($e->getCause() === EntityDamageEvent::CAUSE_FALL || EntityDamageEvent::CAUSE_ENTITY_ATTACK || EntityDamageEvent::CAUSE_PROJECTILE) {
				$e->setCancelled(true);
			}
        }
    }
}