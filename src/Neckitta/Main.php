<?php

namespace Neckitta;

use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\event\Listener;
use pocketmine\command\ConsoleCommandSender; use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent; use pocketmine\utils\TextFormat as C; use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\level\Position;
use pocketmine\event\player\PlayerJoinEvent; use pocketmine\event\entity\EntityDamageByEntityEvent;
class Main extends PluginBase implements Listener {
    private $mysql; public function onEnable() {
        $this->saveDefaultConfig(); $this->getLogger()->info("ServerLevels by Neckitta now enabled!"); $this->getLogger()->info("enabled"); $this->stats = new Config($this->getDataFolder() . "stats.yml", Config::YAML, array()); if (!is_dir($this->getDataFolder())) {
            mkdir($this->getDataFolder());
        } $this->getServer()->getPluginManager()->registerEvents($this, $this); $mysql = new \mysqli($this->getConfig()->get("host"), $this->getConfig()->get("username"), $this->getConfig()->get("password")); if ($mysql->connect_errno) {
            $this->getLogger()->warn("Connect failed: ", $mysqli->connect_error); $this->getServer()->shutDown(); return;
        } $mysql->query("CREATE TABLE " . $this->getConfig()->get("table") . " IF NOT EXIST ( " . "username varchar(255) PRIMARY KEY,\xa" . "level int,\xa" . "exp int " . ");"); $mysql->close(); $this->mysql = new mysqli($this->getConfig()->get("host"), $this->getConfig()->get("username"), $this->getConfig()->get("password"), $this->getConfig()->get("table")); if ($mysql->connect_errno) {
            $this->getLogger()->warn("Connect failed: ", $mysqli->connect_error); $this->getServer()->shutDown(); return;
        }
    } public function onDisable() {
        $this->mysql->close();
    } public function onCommand(CommandSender $sender, Command $command, $label, array $args) : bool {
        switch (strtolower($command->getName())) {
            case "status": $sender->sendMessage(C::ITALIC . C::YELLOW . "=================" . C::GOLD . "Your Status: " . C::YELLOW . "=================="); $sender->sendMessage(C::GOLD . "Level: " . $this->getLevel($sender) . " "); $sender->sendMessage(C::GOLD . "Experience: " . $this->getExp($sender) . "/" . $this->getExpNeededTLU($sender) . " "); $sender->sendMessage(C::GOLD . "Kills: " . $this->getKills($sender) . " "); $sender->sendMessage(C::GOLD . "Deaths: " . $this->getDeaths($sender) . " "); $sender->sendMessage(C::ITALIC . C::YELLOW . "==============================="); break; case "upgrade": $this->initializeLevel($sender); break; case "giveexp": if (isset($args[0]) && isset($args[1]) && is_numeric($args[1])) {
                $this->addExp($args[0], $args[1]); return true; break;
            } case "reduceexp": if (isset($args[0]) && is_numeric($args[0]) && isset($args[1])) {
                    $this->reduceExp($args[0], $args[1]); return true;
                } break; case "reloadMysql": foreach ($this->stats->getAll() as $playerName => $data) {
                    $sql = "INSERT INTO " . $this->getConfig()->get("table") . " (username, level, exp) VALUES ('{$playerName}', {$data["lvl"]}, {$data["exp"]})"; $this->mysql->query($sql);
                } break;
        } return true;
    } public function updateMysqlData(Player $player) {
        $sql = "UPDATE " . $this->getConfig()->get("table") . " SET level={$this->getLevel($player)}, exp={$this->getExp($player)} WHERE username='{$player->getName()}'"; $this->mysql->query($sql);
    } public function initializeLevel($player) {
        $exp = $this->getExp($player); $expn = $this->getExpNeededTLU($player); if ($this->getLevel($player) == 100) {
            $player->sendMessage(C::ITALIC . C::RED . "You have already reached the max level UwU !");
        } if ($exp >= $expn) {
            $this->levelUp($player); $this->reduceExp($player, $expn); $this->setNamedTag($player); $this->addExpNeededTLU($player, $expn * 1); $player->sendMessage(C::YELLOW . "You Are Now Level [" . $this->getLevel($player) . "] !"); $player->addTitle(C::GOLD . "You Are Now Level" . $this->getLevel($player) . "] !");
        } else {
            $player->sendMessage(C::RED . "You don't have enough experience to upgrade!");
        }
    } public function levelUp($player) {
        $this->stats->setNested(strtolower($player->getName()) . ".lvl", $this->stats->getAll()[strtolower($player->getName())]["lvl"] + 1); $this->stats->save(); $this->setNamedTag($player); $this->getServer()->broadcastMessage(C::GOLD . $player->getName() . "UwU This persone has LeveledUP !" . $this->getLevel($player) . "Keep Going !"); $this->updateMysqlData($player);
    } public function setNamedTag($player) {
        $prefix = $this->getServer()->getPluginManager()->getPlugin("PureChat"); $prefix->setPrefix($this->getLevel($player), $player->getPlayer()); $player->sendMessage(C::RED . "neckitta system"); $player->save();
    } public function reduceExp($player, $exp) {
        $this->stats->setNested(strtolower($player->getName()) . ".exp", $this->stats->getAll()[strtolower($player->getName())]["exp"] - $exp); $this->stats->save(); $this->updateMysqlData($player);
    } public function addPlayer($player) {
        $this->stats->setNested(strtolower($player->getName()) . ".lvl", "1"); $this->stats->setNested(strtolower($player->getName()) . ".exp", "0"); $this->stats->setNested(strtolower($player->getName()) . ".expneededtlu", "250"); $this->stats->setNested(strtolower($player->getName()) . ".kills", "0"); $this->stats->setNested(strtolower($player->getName()) . ".deaths", "0"); $this->stats->save(); $this->updateMysqlData($player);
    } public function addDeath($player) {
        $this->stats->setNested(strtolower($player->getName()) . ".deaths", $this->stats->getAll()[strtolower($player->getName())]["deaths"] + 1); $this->stats->save(); $this->updateMysqlData($player);
    } public function addKill($player) {
        $this->stats->setNested(strtolower($player->getName()) . ".kills", $this->stats->getAll()[strtolower($player->getName())]["kills"] + 1); $this->stats->save(); $this->updateMysqlData($player);
    } public function addExp($player, $exp) {
        $this->stats->setNested(strtolower($player) . ".exp", $this->stats->getAll()[strtolower($player)]["exp"] + $exp); $this->stats->save(); $this->updateMysqlData($player);
    } public function addExpNeededTLU($player, $exp) {
        $this->stats->setNested(strtolower($player->getName()) . ".expneededtlu", $this->stats->getAll()[strtolower($player->getName())]["expneededtlu"] + $exp); $this->stats->save(); $this->updateMysqlData($player); } public function getDeaths($player) { return $this->stats->getAll()[strtolower($player->getName())]["deaths"]; } public function getKills($player) { return $this->stats->getAll()[strtolower($player->getName())]["kills"]; } public function getExp($player) { return $this->stats->getAll()[strtolower($player->getName())]["exp"]; } public function getLevel($player) { return $this->stats->getAll()[strtolower($player->getName())]["lvl"]; } public function getExpNeededTLU($player) { return $this->stats->getAll()[strtolower($player->getName())]["expneededtlu"]; } public function onJoin(PlayerJoinEvent $e) { $p = $e->getPlayer(); if (!$this->stats->exists(strtolower($p->getName()))) { $this->addPlayer($p); } $this->setNamedTag($p); } public function onKillDeath(PlayerDeathEvent $event) { $this->addDeath($event->getEntity()); if ($event->getEntity()->getLastDamageCause() instanceof EntityDamageByEntityEvent) { $killer = $event->getEntity()->getLastDamageCause()->getDamager(); if ($killer instanceof Player) { $this->addKill($killer); } } } public function addExpBreak(BlockBreakEvent $e) { $pn = $e->getPlayer()->getName(); $this->addExp($pn, 1); } public function addExpPlace(BlockPlaceEvent $e) { $pn = $e->getPlayer()->getName(); $this->addExp($pn, 1); } public function addExpOnKillDeath(PlayerDeathEvent $e) { $pn = $e->getPlayer()->getName(); $this->addExp($pn, 15); } ?>
