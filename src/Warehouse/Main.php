<?php

namespace Warehouse;

use pocketmine\Player;
use pocketmine\Plugin\PluginBase;
use pocketmine\Server;
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use pocketmine\network\mcpe\protocol\ModalFormResponsePacket;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\inventory\PlayerInventory;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\item\TieredTool;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Config;

class Main extends PluginBase implements Listener{

	public function onEnable(){
		if(!file_exists($this->getDataFolder())){
    		mkdir($this->getDataFolder(), 0744, true);
	}

	/*コンフィグ*/
	$this->getServer()->getPluginManager()->registerEvents($this, $this);
	$this->WH = new Config($this->getDataFolder() . "wh.yml", Config::YAML);
	$this->WHI = new Config($this->getDataFolder() . "info.yml", Config::YAML);
	/*コンフィグ*/
	$this->info = [];

	}

//API	/*==========================================================================================================================*/

	public function sendForm(Player $player, $title, $come, $buttons, $id) {
		
	$pk = new ModalFormRequestPacket(); 
	$pk->formId = $id;
	$this->pdata[$pk->formId] = $player;
	$data = [ 
	'type'    => 'form', 
	'title'   => $title, 
	'content' => $come, 
	'buttons' => $buttons 
	]; 
	$pk->formData = json_encode( $data, JSON_PRETTY_PRINT | JSON_BIGINT_AS_STRING | JSON_UNESCAPED_UNICODE );
	$player->dataPacket($pk);
	$this->lastFormData[$player->getName()] = $data;
	}

	public function sendModal(Player $player, $title, $come, $up, $down, $id) {
		
	$pk = new ModalFormRequestPacket(); 
	$pk->formId = $id;
	$data = [ 
	'type'    => 'modal', 
	'title'   => $title, 
	'content' => $come, 
	'button1' => $up,
	'button2' => $down 
	]; 
	$pk->formData = json_encode( $data, JSON_PRETTY_PRINT | JSON_BIGINT_AS_STRING | JSON_UNESCAPED_UNICODE );
	$player->dataPacket($pk);
	}

	public function sendCustom(Player $player, $title, $elements, $id) {
		
	$pk = new ModalFormRequestPacket(); 
	$pk->formId = $id;
	$this->pdata[$pk->formId] = $player;
	$data = [ 
	'type'    => 'custom_form', 
	'title'   => $title, 
	'content' => $elements
	]; 
	$pk->formData = json_encode( $data, JSON_PRETTY_PRINT | JSON_BIGINT_AS_STRING | JSON_UNESCAPED_UNICODE );
	$player->dataPacket($pk);
	}

	public function startMenu($player) {
		
	$user = $player->getName();
	$buttons[] = [ 
	'text' => "倉庫を使用する", 
	'image' => [ 'type' => 'path', 'data' => "" ] 
	]; //0
	$buttons[] = [ 
	'text' => "持ち物をすべて倉庫に移動させる", 
	'image' => [ 'type' => 'path', 'data' => "" ] 
	]; //1
	$buttons[] = [ 
	'text' => "倉庫を消去する", 
	'image' => [ 'type' => 'path', 'data' => "" ] 
	]; //2
	$this->sendForm($player,"§lメニュー","何をしますか？\n\n",$buttons,12001);
	$this->info[$user] = "form";
	}

//API	/*==========================================================================================================================*/

	public function onJoin(PlayerJoinEvent $event){
	$player = $event->getPlayer();
	$user = $player->getName();
	$this->info[$user] = "";
	}

	public function onSYORIDESU(DataPacketReceiveEvent $event){

	$player = $event->getPlayer();
	$pk = $event->getPacket();
	$user = $player->getName();
		if($pk->getName() == "ModalFormResponsePacket" and isset($this->info[$user])){
		$usera = "1"."$user";
		$data = $pk->formData;
			if($data == "null\n"){
			}else{
				switch($this->info[$user]){
				case "modal":
					switch($pk->formId){
					case 12000://アカウント登録
						if($data == "true\n"){
						$this->WHI->set($user,true);
						$this->WHI->save();
						$this->sendForm($player,"§l完了","アカウント登録が完了しました。\n利用するにはもう一度コマンドを実行してください。",[],0);
						$this->info[$user] = "";
						}elseif($data == "false\n"){
						$this->info[$user] = "";
						}
					break;
					case 12200://持ち物すべて移動
						if($data == "true\n"){
						$si = $player->getInventory()->getSize();
							for($is = 1; $is <= $si; ++$is){
							$item = $player->getInventory()->getItem($is-1);
							$i = 1;
							if($item->getId() !== 0){
								while($i){
								$ib = $i."$user";
									if($this->WH->exists($ib)){
									$it = $this->WH->get($ib);
										if($it["ID"] == $item->getID() && $it["META"] == $item->getDamage()){//かぶってるアイテムがあったら
										$a = $it["CO"]+$item->getCount();
										$this->WH->set($ib,["ID"=>$it["ID"],"META"=>$it["META"],"CO"=>$a]);
										$this->WH->save();
										break;
										}else{
										$i++;
										}
									}elseif(!$this->WH->exists($ib) and $is !== $si){
									$ibs = $i."$user";
									$this->WH->set($ibs,["ID"=>$item->getID(),"META"=>$item->getDamage(),"CO"=>$item->getCount()]);
									$this->WH->save();
									break;
									}else{
									$i--;
									break;
									}
								}
							}
							}
						$this->sendForm($player,"§l完了","倉庫への移動が完了しました。",[],0);
						$player->getInventory()->clearAll();
						$this->info[$user] = "";
						}else{
						$this->startMenu($player);
						}

					break;
					case 12300://持ち物すべて移動
						if($data == "true\n"){
						$i = 1;
							while($i){
							$ib = $i."$user";
								if($this->WH->exists($ib)){
								$this->WH->remove($ib);
								$this->WH->save();
								$i++;
								}else{
								$i--;
								break;
								}
							}
						$this->WHI->remove($user);
						$this->WHI->save();
						$this->sendForm($player,"§l完了","倉庫が完全に消去されました。",[],0);
						$this->info[$user] = "";
						}else{
						$this->startMenu($player);
						}

					break;
					}
				case "form":
					switch($pk->formId){
					case 12001:
						if($data == 0){
						$buttons[] = [ 
						'text' => "自分の持ち物", 
						]; //0
						$buttons[] = [ 
						'text' => "自分の倉庫", 
						]; //1
						$buttons[] = [ 
						'text' => "戻る", 
						]; //2
						$this->sendForm($player,"§lメニュー/倉庫を使用する","何をしますか？\n\n",$buttons,12100);
						}elseif($data == 1){
						$this->sendModal($player,"§lメニュー/持ち物をすべて倉庫に移動させる","本当に実行しますか？\n*§a持ち物からアイテムはなくなります§f*\n","はい","戻る",12200);
						$this->info[$user] = "modal";
						}elseif($data == 2){
						$this->sendModal($player,"§lメニュー/倉庫を消去する","本当に実行しますか？\n*§c倉庫のアイテムは完全に消えます§f*\n","はい","戻る",12300);
						$this->info[$user] = "modal";
						}
					break;
					case 12100:
						if($data == 0){//自分の持ち物
						$si = $player->getInventory()->getSize();
						$buttons[] = [ 
						'text' => "戻る", 
						]; 
							for($is = 1; $is <= $si; ++$is){
							$item = $player->getInventory()->getItem($is-1);
								if($item->getId() !== 0){
								$buttons[] = [ 
								'text' => "§l§2名前§8: {$item->getName()} ({$item->getID()}:{$item->getDamage()}) ({$item->getCount()}個)", 
								]; 
								$this->MYITEM[$user][$is] = $item;
								}
							}	
						$this->sendForm($player,"§l倉庫を使用する/自分の持ち物","倉庫に送るアイテムを選んでください。\n\n",$buttons,13000);
						}elseif($data == 1){//自分の倉庫
						$i = 1;
						$buttons[] = [ 
						'text' => "戻る", 
						]; 
							while($i){
							$ib = $i."$user";
								if($this->WH->exists($ib)){
								$it = $this->WH->get($ib);
								$buttons[] = [ 
								'text' => "§l§3名前§8: {$it["NAME"]} ({$item->getID()}:{$item->getDamage()}) ({$it["CO"]}個)", 
								]; 
								$this->WHITEM[$user][$i] = $it;
								$i++;
								}else{
								$i--;
								break;
								}
							}
						$this->sendForm($player,"§l倉庫を使用する/自分の倉庫","手持ちに送るアイテムを選んでください。\n\n",$buttons,14000);
						}elseif($data == 2){
						$this->startMenu($player);
						}
					break;

					case 13000://倉庫にアイテム送信
						if($data == 0){//自分の持ち物
						$this->startMenu($player);
						}else{//自分の倉庫
						$da = json_decode($data);
						$item = $this->MYITEM[$user][$da];
						$elements[] = [ 
						'type' => "label",
						'text' => "{$item->getName()} ({$item->getID()}:{$item->getDamage()}) を§a倉庫§fに送信します。\n*§c0個の場合、送信ができません。§f*\n", 
						]; 
						for($i = 0; $i <= $item->getCount(); $i++){
						$a[] = "".$i."";
						}
						$elements[] = [ 
						'type' => "step_slider",
						'text' => "送信する数", 
						'steps' => $a,
						'defaultIndex' => "1"
						]; 
						$this->MYITEMS[$user] = $item;
						$this->sendCustom($player,"§l自分の持ち物/倉庫に送信",$elements,13001);
						$this->info[$user] = "custom";
						}
					break;

					case 14000://手持ちにアイテム送信
						if($data == 0){//自分の持ち物
						$this->startMenu($player);
						}else{//自分の倉庫
						$da = json_decode($data);
						$item = $this->WHITEM[$user][$da];
						$elements[] = [ 
						'type' => "label",
						'text' => "{$item["NAME"]} ({$item->getID()}:{item->getDamage()}) を§b手持ち§fに送信します。\n*§c一度に64個までしか送信できません。§f*\n*§c0個の場合、送信ができません。§f*\n", 
						]; 
						for($i = 0; $i <= $item["CO"]; $i++){
						$a[] = "".$i."";
						}
						$elements[] = [ 
						'type' => "step_slider",
						'text' => "送信する数", 
						'steps' => $a,
						'defaultIndex' => "0"
						]; 
						$this->WHITEMS[$user] = $da;
						$this->sendCustom($player,"§l自分の倉庫/手持ちに送信",$elements,14001);
						$this->info[$user] = "custom";
						}
					break;
					}
				case "custom":
					switch($pk->formId){
					case 13001://倉庫に送信
					$da = json_decode($data)[1]++;//個数
					if($da == 0){
					$this->sendForm($player,"§lエラー","§c0個の場合、送信ができません。\nもう一度やり直してください。",[],0);
					$this->startMenu($player);
					}else{
					$item = $this->MYITEMS[$user];
					$player->getInventory()->removeItem($item);
					$item->setCount($item->getCount() - $da);
					$player->getInventory()->addItem($item);
					$i = 1;
					$a = false;
						while($i){
						$ib = $i."$user";
							if($this->WH->exists($ib)){
							$it = $this->WH->get($ib);
								if($it["ID"] == $item->getID() && $it["META"] == $item->getDamage()){//かぶってるアイテムがあったら
								$a = $it["CO"]+$da;
								$this->WH->set($ib,["ID"=>$it["ID"],"META"=>$it["META"],"CO"=>$a]);
								$this->WH->save();
								$a = true;
								break;
								}else{
								$i++;
								}
							}elseif(!$this->WH->exists($ib) and $a !== true){
							$ibs = $i."$user";
							$this->WH->set($ibs,["ID"=>$item->getID(),"META"=>$item->getDamage(),"CO"=>$da]);
							$this->WH->save();
							break;
							}else{
							$i--;
							break;
							}
						}
					$this->sendForm($player,"§l完了","倉庫への移動が完了しました。",[],0);
					$this->info[$user] = "";
					}
					break;
					case 14001://手持ちに送信
					$da = json_decode($data)[1]++;//個数
					if($da == 0){
					$this->sendForm($player,"§lエラー","§c0個の場合、送信ができません。\nもう一度やり直してください。",[],0);
					$this->startMenu($player);
					}else{
					$count = $this->WHITEMS[$user];
					$ib = $this->WHITEMS[$user]."$user";
					$it = $this->WH->get($ib);
					$a = $it["CO"] - $da;
						if($a == 0){
						$this->WH->remove($ib);
						$this->WH->save();
						$counts = $count+1;
							while($counts){
							$ibs = $counts."$user";
								if($this->WH->exists($ibs)){ 
								$its = $this->WH->get($ibs);
								$coun = $counts-1;
								$ibss = $coun."$user";
								$this->WH->set($ibss,["ID"=>$its["ID"],"META"=>$its["META"],"CO"=>$its["CO"]]);
								$this->WH->remove($ibs);
								$this->WH->save();
								$counts++;
								}else{
								$counts--;
								break;
								}
							}
						}else{
						$this->WH->set($ib,["ID"=>$it["ID"],"META"=>$it["META"],"CO"=>$a]);
						$this->WH->save();
						}
					$items = Item::get($it["ID"],$it["META"],$da);
					$player->getInventory()->addItem($items);
					$this->sendForm($player,"§l完了","手持ちへの移動が完了しました。",[],0);
					$this->info[$user] = "";
					}
					break;
					}
				break;
				}
			}
		}
	}





    public function onCommand(CommandSender $sender, Command $command, string $label, array $args):bool{
        
	if($sender->getName() === "CONSOLE") {
	$sender->sendMessage(">>§cこのコマンドはゲーム内で使ってください");
	return false;
        }else{
	switch ($command->getName()) {

	case "whouse":
		$player = $sender;
		$user = $sender->getName();
		$usera = "1"."$user";
		$check = true;
		foreach($player->getInventory()->getContents() as $item){
		    if($item->hasEnchantments()){
		        $player->sendMessage("§c>>インベントリにエンチャントされたアイテムがあります");
                        $player->sendMessage("§c>>エンチャントされたアイテムをインベントリから抜いてください");
                        $check = false;
                        break;
                    }else{
                        if($item->getDamage() > 0){
                            if($item instanceof TieredTool){
                                $player->sendMessage("§c>>インベントリに耐久値が減っているアイテムがあります");
                                $player->sendMessage("§c>>耐久値が減っているアイテムをインベントリから抜いてください");
                                $check = false;
                                break;
			    }
			}
		    }
		}
        if(!$this->WHI->exists($user)){
            $this->sendModal($player,"§lアカウント登録","あなたにはまだ自分の倉庫がありません。\n作成しますか？\n","はい","いいえ",12000);
			$this->info[$user] = "modal";
        }else{
            if($check){
                $this->startMenu($player);
            }
        }
		return true;
}
}
}
}
