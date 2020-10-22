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

use pocketmine\level\Location;
use pocketmine\level\particle\Particle;
use pocketmine\network\mcpe\protocol\BatchPacket;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\Player;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\utils\TextFormat as C;

class ParticleTask extends AsyncTask{
    /** @var BatchPacket[][] floorVec => BatchPacket[] */
    public static $cache = [];

    /** @var float */
    private $timing;

    /** @var string */
    private $playerName;

    /** @var string */
    private $hash;

    /** @var bool Flag to not proceed if cached */
    private $cached;

    public static function make(Player $player, ?float $time = null){
        if($cached = $time === null){
            $time = microtime(true);
        }
        $playerName = $player->getName();
        $loc = $player->asLocation();
        $loc->y += $player->getEyeHeight();
        $hash = self::loc2hash($loc);
        $server = Server::getInstance();
        if(isset(self::$cache[$hash])){
            $time2 = microtime(true);
            foreach(self::$cache[$hash] as $packet){
                $player->sendDataPacket($packet);
            }

            $msgs = [
                ($cached ? C::GREEN : C::AQUA) . Test::timing($time),
                ($cached ? C::DARK_GREEN : C::DARK_AQUA) . Test::timing($time2)
            ];
            $server->getLogger()->debug(implode(" / ", $msgs));
            $player->sendTip(implode("\n", $msgs));
        }else{
            $server->getAsyncPool()->submitTask(new ParticleTask($playerName, $hash));
        }
    }

    public function __construct(string $playerName, string $hash){
        $this->timing = microtime(true);
        $this->playerName = $playerName;
        $this->hash = $hash;
        $this->cached = isset(self::$cache[$this->hash]);
    }

    public function onRun(){
        if($this->cached){
            $this->setResult([]);
            return;
        }

        $payloads = [];
        $packet = new BatchPacket();
        $loc = self::hash2loc($this->hash);
        $xz = cos(deg2rad($loc->pitch));
        $x = -$xz * sin(deg2rad($loc->yaw));
        $y = -sin(deg2rad($loc->pitch));
        $z = $xz * cos(deg2rad($loc->yaw));

        $pk = new LevelEventPacket;
        $pk->evid = LevelEventPacket::EVENT_ADD_PARTICLE_MASK | Particle::TYPE_REDSTONE;
        $pk->data = 1;

        for($i = 0; $i < Test::POINTS; ++$i){
            if($i % 500 === 0){
                $packet->encode();
                $payloads[] = $packet->payload;
                $packet = new BatchPacket();
            }

            $t = Test::MAX_TIME / Test::POINTS * $i - Test::MIN_TIME / 10;
            $pk->position = $loc->add( $t * $x,  $t * $y,  $t * $z);
            $pk->encode();
            $packet->addPacket($pk);
        }
        $packet->encode();
        $payloads[] = $packet->payload;
        $this->setResult($payloads);
    }

    public function onCompletion(Server $server){
        if(($player = $server->getPlayer($this->playerName)) === null)
            return;
        self::$cache[$this->hash] = [];

        foreach($this->getResult() as $payload){
            $packet = new BatchPacket();
            $packet->payload = $payload;
            $packet->encode();
            self::$cache[$this->hash][] = $packet;
            $player->sendDataPacket($packet);
        }
        self::make($player, $this->timing);
    }

    public static function loc2hash(Location $loc) : string{
        return number_format($loc->x, 1) . ":" . number_format($loc->y, 1) . ":" . number_format($loc->z, 1) . ":" . number_format($loc->yaw) . ":" . number_format($loc->pitch);
    }

    public static function hash2loc(string $hash) : Location{
        return new Location(...array_map('\floatval', explode(':', $hash)));
    }
}