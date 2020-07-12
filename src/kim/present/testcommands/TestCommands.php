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

use leinne\sagwa\player\Player;
use pocketmine\block\Block;
use pocketmine\block\BlockBreakInfo;
use pocketmine\block\BlockIdentifier;
use pocketmine\block\BlockToolType;
use pocketmine\command\Command;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\event\Listener;
use pocketmine\item\LegacyStringToItemParser;
use pocketmine\lang\TranslationContainer;
use pocketmine\nbt\JsonNbtParser;
use pocketmine\nbt\NbtDataException;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;

class TestCommands extends PluginBase implements Listener{
    /**
     * Called when the plugin is enabled
     */
    public function onEnable() : void{
        $this->getServer()->getCommandMap()->register(strtolower($this->getName()), new PluginCommand('i', $this, new class() implements CommandExecutor{
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
                    $item = LegacyStringToItemParser::getInstance()->parse($params[0]);
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
                    }catch(NbtDataException $e){
                        $sender->sendMessage(new TranslationContainer("commands.give.tagError", [$e->getMessage()]));
                        return true;
                    }

                    $item->setNamedTag($tags);
                }

                $sender->getInventory()->setItemInHand(clone $item);
                return true;
            }
        }));
        $this->getServer()->getCommandMap()->register(strtolower($this->getName()), new PluginCommand('b', $this, new class() implements CommandExecutor{
            public function onCommand(CommandSender $sender, Command $command, $label, array $params) : bool{
                if(!$sender instanceof Player){
                    $sender->sendMessage(TextFormat::YELLOW . "인게임에서 실행하세요");
                    return true;
                }
                $id = $params[0] ?? 0;
                $meta = $params[1] ?? 0;
                $sender->getWorld()->setBlock($sender->getPosition()->floor(), new Block(new BlockIdentifier((int) $id, (int) $meta), "t", new BlockBreakInfo(0.1, BlockToolType::NONE)));
                return true;
            }
        }));
    }
}
