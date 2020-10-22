<?php

/**
 *  ____                           _   _  ___
 * |  _ \ _ __ ___  ___  ___ _ __ | |_| |/ (_)_ __ ___
 * | |_) | '__/ _ \/ __|/ _ \ '_ \| __| ' /| | '_ ` _ \
 * |  __/| | |  __/\__ \  __/ | | | |_| . \| | | | | | |
 * |_|   |_|  \___||___/\___|_| |_|\__|_|\_\_|_| |_| |_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the MIT License. see <https://opensource.org/licenses/MIT>.
 *
 *
 * @author      PresentKim (debe3721@gmail.com)
 * @link        https://github.com/PresentKim
 * @license     https://opensource.org/licenses/MIT MIT License
 *
 *   (\ /)
 *  ( . .) ♥
 *  c(")(")
 */

declare(strict_types=1);

namespace kim\present\testcommands;

use blugin\lib\invmenu\plus\InvMenuPlus;
use blugin\utils\bannerfactory\BannerFactory;
use blugin\virtualchest\VirtualChest;
use blugin\virtualchest\VirtualChestInstance;
use muqsit\invmenu\InvMenuHandler;
use muqsit\invmenu\metadata\SingleBlockMenuMetadata;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockIds;
use pocketmine\command\Command;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\event\Listener;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\lang\TranslationContainer;
use pocketmine\level\particle\Particle;
use pocketmine\nbt\JsonNbtParser;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\network\mcpe\convert\RuntimeBlockMapping;
use pocketmine\network\mcpe\protocol\BatchPacket;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\types\WindowTypes;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;
use pocketmine\utils\TextFormat as C;

class TestCommands extends PluginBase implements Listener{
    public const TYPE_DISPENSER = "test:dispenser";

    public function onEnable() : void{
        if(!InvMenuHandler::isRegistered()){
            InvMenuHandler::register($this);
        }
        $this->registerCustomMenuTypes();

        $this->getServer()->getCommandMap()->register(strtolower($this->getName()), $this->makeCommand('i', new class() implements CommandExecutor{
            public function onCommand(CommandSender $sender, Command $command, $label, array $params) : bool{
                if(!$sender instanceof Player){
                    $sender->sendMessage(TextFormat::YELLOW . "인게임에서 실행하세요");
                    return true;
                }

                if(count($params) < 1){
                    throw new InvalidCommandSyntaxException();
                }

                try{
                    $item = ItemFactory::fromStringSingle($params[0]);
                }catch(\InvalidArgumentException $e){
                    $sender->sendMessage(new TranslationContainer(TextFormat::RED . "%commands.give.item.notFound", [$params[0]]));
                    return true;
                }

                if(!isset($params[1])){
                    $item->setCount($item->getMaxStackSize());
                }else{
                    $item->setCount((int) $params[1]);
                }

                if(isset($params[2])){
                    $data = implode(" ", array_slice($params, 2));
                    try{
                        $tags = JsonNbtParser::parseJson($data);
                    }catch(\Exception $e){
                        $sender->sendMessage(new TranslationContainer("commands.give.tagError", [$e->getMessage()]));
                        return true;
                    }

                    $item->setNamedTag($tags);
                }
                $sender->getInventory()->setItemInHand(clone $item);
                return true;
            }
        }));
        $this->getServer()->getCommandMap()->register(strtolower($this->getName()), $this->makeCommand('ii', new class() implements CommandExecutor{
            /**
             * @param CommandSender $sender
             * @param Command       $command
             * @param string        $label
             * @param array         $params
             *
             * @return bool
             */
            public function onCommand(CommandSender $sender, Command $command, $label, array $params) : bool{
                if(!$sender instanceof Player){
                    $sender->sendMessage(TextFormat::YELLOW . "인게임에서 실행하세요");
                    return true;
                }

                if(count($params) < 1){
                    throw new InvalidCommandSyntaxException();
                }

                try{
                    $item = ItemFactory::fromStringSingle($params[0]);
                }catch(\InvalidArgumentException $e){
                    $sender->sendMessage(new TranslationContainer(TextFormat::RED . "%commands.give.item.notFound", [$params[0]]));
                    return true;
                }

                if(!isset($params[1])){
                    $item->setCount($item->getMaxStackSize());
                }else{
                    $item->setCount((int) $params[1]);
                }

                if(isset($params[2])){
                    $data = implode(" ", array_slice($params, 2));
                    try{
                        $tags = JsonNbtParser::parseJson($data);
                    }catch(\Exception $e){
                        $sender->sendMessage(new TranslationContainer("commands.give.tagError", [$e->getMessage()]));
                        return true;
                    }

                    $item->setNamedTag($tags);
                }

                $item->setNamedTagEntry(new ListTag("ench", [
                    new CompoundTag("", [
                        new ShortTag("id", -1),
                        new ShortTag("lvl", 0)
                    ])
                ]));
                //$item->addEnchantment(new EnchantmentInstance(new Enchantment(-1, "", 0, 0, 0, 1)));
                $sender->getInventory()->setItemInHand(clone $item);
                return true;
            }
        }));
        $this->getServer()->getCommandMap()->register(strtolower($this->getName()), $this->makeCommand('im', new class() implements CommandExecutor{
            /**
             * @param CommandSender $sender
             * @param Command       $command
             * @param string        $label
             * @param array         $params
             *
             * @return bool
             */
            public function onCommand(CommandSender $sender, Command $command, $label, array $params) : bool{
                if(!$sender instanceof Player){
                    $sender->sendMessage(TextFormat::YELLOW . "인게임에서 실행하세요");
                    return true;
                }
                if(count($params) < 1){
                    throw new InvalidCommandSyntaxException();
                }

                $sender->getInventory()->setItemInHand($sender->getInventory()->getItemInHand()->setCustomName(str_replace("#n", "\n", implode(" ", $params))));
                return true;
            }
        }));
        $this->getServer()->getCommandMap()->register(strtolower($this->getName()), $this->makeCommand('vci', new class() implements CommandExecutor{
            /**
             * @param CommandSender $sender
             * @param Command       $command
             * @param string        $label
             * @param array         $params
             *
             * @return bool
             */
            public function onCommand(CommandSender $sender, Command $command, $label, array $params) : bool{
                if(!$sender instanceof Player){
                    $sender->sendMessage(TextFormat::YELLOW . "인게임에서 실행하세요");
                    return true;
                }
                if(count($params) < 1){
                    throw new InvalidCommandSyntaxException();
                }

                $heldItem = $sender->getInventory()->getItemInHand();
                $heldItem->setCustomName(VirtualChest::getInstance()->getTranslator()->translateTo("coupon.name", [$count = (int) $params[0] ?? 10], $sender));
                $heldItem->getNamedTag()->setInt(VirtualChestInstance::TAG_NAME, $count);
                $sender->getInventory()->setItemInHand($heldItem);
                return true;
            }
        }));
        $this->getServer()->getCommandMap()->register(strtolower($this->getName()), $this->makeCommand('b', new class() implements CommandExecutor{
            public function onCommand(CommandSender $sender, Command $command, $label, array $params) : bool{
                if(!$sender instanceof Player){
                    $sender->sendMessage(TextFormat::YELLOW . "인게임에서 실행하세요");
                    return true;
                }
                $id = (int) ($params[0] ?? 0);
                $meta = (int) ($params[1] ?? 0);
                try{
                    $block = BlockFactory::get($id, $meta);
                }catch(\InvalidArgumentException $e){
                    $sender->sendMessage("Invalid block!");
                    $block = new Block($id, $meta);
                }
                $sender->getLevel()->setBlock($sender->getPosition()->floor(), $block);
                return true;
            }
        }));
        $this->getServer()->getCommandMap()->register(strtolower($this->getName()), $this->makeCommand('b1', new class() implements CommandExecutor{
            public function onCommand(CommandSender $sender, Command $command, $label, array $params) : bool{
                if(!$sender instanceof Player){
                    $sender->sendMessage(TextFormat::YELLOW . "인게임에서 실행하세요");
                    return true;
                }
                $id = (int) ($params[0] ?? 0);
                $meta = (int) ($params[1] ?? 0);
                try{
                    $block = BlockFactory::get($id, $meta);
                }catch(\InvalidArgumentException $e){
                    $sender->sendMessage("Invalid block!");
                    $block = new Block($id, $meta);
                }
                $pk = new UpdateBlockPacket();
                $pk->x = $sender->getPosition()->getFloorX();
                $pk->y = $sender->getPosition()->getFloorY();
                $pk->z = $sender->getPosition()->getFloorZ();
                $pk->blockRuntimeId = RuntimeBlockMapping::toStaticRuntimeId($block->getId(), $block->getDamage());
                $pk->dataLayerId = UpdateBlockPacket::DATA_LAYER_LIQUID;

                $packets[] = $pk;
                $sender->getLevel()->setBlock($sender->getPosition()->floor(), $block);
                return true;
            }
        }));
        $this->getServer()->getCommandMap()->register(strtolower($this->getName()), $this->makeCommand('if', new class() implements CommandExecutor{
            public function onCommand(CommandSender $sender, Command $command, $label, array $params) : bool{
                if(!$sender instanceof Player){
                    $sender->sendMessage(TextFormat::YELLOW . "인게임에서 실행하세요");
                    return true;
                }
                for($y = 0, $height = mt_rand(3, 6); $y < $height; ++$y){
                    $sender->getLevel()->setBlock($sender->getPosition()->floor()->add(0, $y, 0), new Block(BlockIds::LOG, 0));
                }
                return true;
            }
        }));
        $this->getServer()->getCommandMap()->register(strtolower($this->getName()), $this->makeCommand('numpad', new class() implements CommandExecutor{
            public function onCommand(CommandSender $sender, Command $command, $label, array $params) : bool{
                if(!$sender instanceof Player){
                    $sender->sendMessage(TextFormat::YELLOW . "인게임에서 실행하세요");
                    return true;
                }
                foreach(BannerFactory::PATTERN_NUM_LIST as $patternName){
                    $sender->getInventory()->addItem(BannerFactory::make($patternName, [(int) ($params[0] ?? 0), (int) ($params[1] ?? 12)]));
                }
                return true;
            }
        }));
        $this->getServer()->getCommandMap()->register(strtolower($this->getName()), $this->makeCommand('rl', new class() implements CommandExecutor{
            public function onCommand(CommandSender $sender, Command $command, $label, array $params) : bool{
                if(!$sender instanceof Player){
                    $sender->sendMessage(TextFormat::YELLOW . "인게임에서 실행하세요");
                    return true;
                }
                foreach(BannerFactory::PATTERN_ARROW_LIST as $patternName){
                    $sender->getInventory()->addItem(BannerFactory::make($patternName, [(int) ($params[0] ?? 0), (int) ($params[1] ?? 12)]));
                }
                return true;
            }
        }));

        $this->getServer()->getCommandMap()->register(strtolower($this->getName()), $this->makeCommand('read', new class() implements CommandExecutor{
            public function onCommand(CommandSender $sender, Command $command, $label, array $params) : bool{
                $line = TestCommands::getInput("이거 동의함?", "n", "y/N");
                $sender->sendMessage($line);
                return true;
            }
        }));
        $this->getServer()->getCommandMap()->register(strtolower($this->getName()), $this->makeCommand('dis', new class() implements CommandExecutor{
            public function onCommand(CommandSender $sender, Command $command, $label, array $params) : bool{
                if(!$sender instanceof Player){
                    $sender->sendMessage(TextFormat::YELLOW . "인게임에서 실행하세요");
                    return true;
                }

                static $menu;
                if(!isset($menu)){
                    $menu = InvMenuPlus::create(TestCommands::TYPE_DISPENSER);
                }
                $menu->setName($sender->getName());
                $menu->send($sender);
                return true;
            }
        }));
/*
        $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(function() : void{
            foreach($this->getServer()->getOnlinePlayers() as $player){
                $itemId = $player->getInventory()->getItemInHand()->getId();
                if($itemId === ItemIds::AIR){
                    ParticleTask::make($player);
                }elseif($itemId === ItemIds::CHEST){
                    $time = microtime(true);
                    $loc = $player->asLocation();
                    $loc->y += $player->getEyeHeight();
                    $hash = ParticleTask::loc2hash($loc);
                    $xz = cos(deg2rad($loc->pitch));
                    $x = -$xz * sin(deg2rad($loc->yaw));
                    $y = -sin(deg2rad($loc->pitch));
                    $z = $xz * cos(deg2rad($loc->yaw));
                    if(!($cahced = isset(ParticleTask::$cache[$hash]))){
                        $packets = [];
                        $packet = new BatchPacket();

                        $pk = new LevelEventPacket();
                        $pk->evid = LevelEventPacket::EVENT_ADD_PARTICLE_MASK | Particle::TYPE_REDSTONE;
                        $pk->data = 1;
                        for($i = 0; $i < Test::POINTS; ++$i){
                            if($i % 500 === 0){
                                $packet->encode();
                                $packets[] = $packet;
                                $packet = new BatchPacket();
                            }

                            $t = Test::MAX_TIME / Test::POINTS * $i - Test::MIN_TIME / 10;
                            $pk->position = $loc->add( $t * $x,  $t * $y,  $t * $z);
                            $pk->encode();
                            $packet->addPacket($pk);
                        }
                        $packet->encode();
                        $packets[] = $packet;
                        ParticleTask::$cache[$hash] = $packets;
                    }
                    foreach(ParticleTask::$cache[$hash] as $packet){
                        $player->sendDataPacket($packet);
                    }

                    $this->getServer()->getLogger()->debug($msg = ($cahced ? C::GREEN : C::AQUA) . Test::timing($time));
                    $player->sendTip($msg);
                }else{
                    $time = microtime(true);
                    $loc = $player->asLocation();
                    $loc->y += $player->getEyeHeight();
                    $xz = cos(deg2rad($loc->pitch));
                    $x = -$xz * sin(deg2rad($loc->yaw));
                    $y = -sin(deg2rad($loc->pitch));
                    $z = $xz * cos(deg2rad($loc->yaw));
                    for($i = 0; $i < Test::POINTS; ++$i){
                        $pk = new LevelEventPacket();
                        $pk->evid = LevelEventPacket::EVENT_ADD_PARTICLE_MASK | Particle::TYPE_REDSTONE;
                        $pk->data = 1;
                        $t = Test::MAX_TIME / Test::POINTS * $i - Test::MIN_TIME / 10;
                        $pk->position = $loc->add( $t * $x,  $t * $y,  $t * $z);
                        $player->sendDataPacket($pk);
                    }
                    $this->getServer()->getLogger()->debug($msg = C::GOLD . Test::timing($time));
                    $player->sendTip($msg);
                }
            }
        }), 1);
        */
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function registerCustomMenuTypes() : void{
        $type = new SingleBlockMenuMetadata(
            self::TYPE_DISPENSER,                // identifier
            9,                                   // number of slots
            WindowTypes::DISPENSER,              // mcpe window type id
            BlockFactory::get(Block::DISPENSER), // Block
            "Dispenser" // block entity identifier
        );
        InvMenuHandler::registerMenuType($type);
    }

    public function makeCommand(string $name, CommandExecutor $executor, string $description = "") : PluginCommand{
        $command = new PluginCommand($name, $this);
        $command->setExecutor($executor);
        $command->setDescription($description);
        return $command;
    }


    public static function readLine() : string{
        return trim((string) fgets(STDIN));
    }

    public static function getInput(string $message, string $default = "", string $options = "") : string{
        $message = "[?] " . $message;

        if($options !== "" or $default !== ""){
            $message .= " (" . ($options === "" ? $default : $options) . ")";
        }
        $message .= ": ";

        echo $message;

        $input = self::readLine();

        return $input === "" ? $default : $input;
    }
}
