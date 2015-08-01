<?php echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>"; ?> 
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="ja" xml:lang="ja">
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<title>Component Rank Calculator</title>
<meta http-equiv="content-style-type" content="text/css" />
<meta http-equiv="content-script-type" content="text/javascript" />
<link rel="stylesheet" type="text/css" media="screen" href="style.css" />
<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript" src="js/jquery.dataTables.js"></script>
<script type="text/javascript">
$(function(){
	$('#result').dataTable({
		"bPaginate": false,
		"bLengthChange": false,
		"bFilter": false,
		"bSort": true,
		"bInfo": false,
		"bAutoWidth": false
	});
});
</script>
</head>
<body>
<div id="page">
<div id="header"><h1>Component Rank Calculator</h1></div>
<div id="main">
<?php
	// -----------------------------------------------------
	// 設定
	// -----------------------------------------------------

	// Classycleファイルの設定
//	$file_class = "xml/jmp.xml";
//	$file_class = "xml/junit.xml";
	$file_class = "xml/classycle.xml";
//	$file_class = "xml/jgraphx.xml";
//	$file_class = "xml/rabbit.xml";
//	$file_class = "xml/mantissa.xml";

	// CCFinderファイルの設定
//	$file_clone = "clone/jmp.txt";
//	$file_clone = "clone/junit.txt";
	$file_clone = "clone/classycle.txt";
//	$file_clone = "clone/jgraphx.txt";
//	$file_clone = "clone/rabbit.txt";
//	$file_clone = "clone/mantissa.txt";

	// 「遠い」と判断するクラス間の距離の定義（これ「以上」だと遠いと判断）
	$far_dist = 4;

	// 実行環境の設定
	ini_set('memory_limit' ,'512M');
	ini_set('max_execution_time' ,'120');

	// -----------------------------------------------------
	// ファイルのロード
	// -----------------------------------------------------

	// Classycleファイルのロード
	$xml = simplexml_load_file($file_class, 'SimpleXMLElement', LIBXML_NOCDATA);

	// CCFinderファイルのロード
	$ccf = @file($file_clone, FILE_IGNORE_NEW_LINES);

	// -----------------------------------------------------
	// CCFinderの結果を解析し、クローンリストを作成
	// -----------------------------------------------------

	// ファイルリストを抽出し、配列$list_fileに格納
	$list_start = array_search("#begin{file description}", $ccf);
	$list_end = array_search("#end{file description}", $ccf);
	$list_length = $list_end - $list_start - 1;
	$list_file = array_slice($ccf , ($list_start+1), $list_length);

	// 余分なところを除去し、ファイルパスだけにする
	for($i = 0;$i < count($list_file);$i++){
		$path_start = strrpos($list_file[$i], ":") - 1;
		$list_file[$i] = substr($list_file[$i], $path_start);
	}
	// XMLファイルに出てくる最初のクラス名を用い、フォルダのパスを特定
	for($i = 0;$i < count($xml->classes->class);$i++){
		$class_example = (string)($xml->classes->class[$i]['name']);
		$class_example = str_replace(".", "\\", $class_example);
		$class_example = 	$class_example.".java";
		for($j = 0;$j < count($list_file);$j++){
			if(strpos($list_file[$j], $class_example) == true){
				$key_class_example = $j;
				break;
			}
		}
		if($key_class_example == true){
			break;
		}
	}
	$folder_path = substr($list_file[$key_class_example], 0, strrpos($list_file[$key_class_example], $class_example));

	// フォルダのパスを除去し、クラス名だけにする
	for($i = 0;$i < count($list_file);$i++){
		$foldername_start = strrpos($list_file[$i], $folder_path) + strlen($folder_path);
		$foldername_end = strrpos($list_file[$i], ".java");
		$list_file[$i] = substr($list_file[$i], $foldername_start, ($foldername_end - $foldername_start));
		$list_file[$i] = str_replace("\\", ".", $list_file[$i]);
	}

	// クローンリストを抽出し、2次元配列$list_cloneに格納（$list_clone[セットID][セットの内容]）
	$set_start = array_keys($ccf, "#begin{set}");
	$set_end = array_keys($ccf, "#end{set}");
	for($i = 0;$i < count($set_start);$i++){
		$set_length = $set_end[$i] - $set_start[$i] - 1;
		$list_clone[$i] = array_slice($ccf , ($set_start[$i]+1), $set_length);
	}

	// セットの内容をクラス名の配列にする
	for($i = 0;$i < count($list_clone);$i++){
		for($j = 0;$j < count($list_clone[$i]);$j++){
			$file_id_start = strpos($list_clone[$i][$j], ".") + 1;
			$file_id_end = strpos($list_clone[$i][$j], "\t");
			$list_clone[$i][$j] = substr($list_clone[$i][$j], $file_id_start, ($file_id_end - $file_id_start));
			$list_clone[$i][$j] = intval($list_clone[$i][$j]);
			$list_clone[$i][$j] = $list_file[$list_clone[$i][$j]];
		}
		// 重複を削除
		$list_clone[$i] = array_unique($list_clone[$i]);
		$list_clone[$i] = array_merge($list_clone[$i]);
	}

	// セットの内容が1つのクラスだけなら削除（同一クラス内にクローンがあるだけ）
	for($i = (count($list_clone) - 1);$i >= 0;$i--){
		if((count($list_clone[$i])) == 1){
			unset($list_clone[$i]);
		}
	}
	$list_clone = array_merge($list_clone);

	// -----------------------------------------------------
	// クラス間の距離が遠いものはクローンリストから除外
	// -----------------------------------------------------

	// クラス間の距離を計算する関数
	function calc_dist($point_a, $point_b){
		// クラス名を「.」で分割
		$point_a_split = explode(".", $point_a);
		$point_b_split = explode(".", $point_b);
		// クラス名の共通部分以外の要素数の合計がクラス間の距離となる
		$common = array_intersect_assoc($point_a_split, $point_b_split);
		$dist = count(array_diff($point_a_split, $common)) + count(array_diff($point_b_split, $common));

		return $dist;
	}

	for($i = count($list_clone) - 1;$i >= 0;$i--){
		// 距離を計算するクラス
		$points = $list_clone[$i];

		// calc_dist()を使って$pointsに含まれるクラスの全組み合わせの距離を測定
		// 最も近いクラスへの距離を$nearestに格納
		$nearest = array_fill(0, count($points), 9999);
		for($j = 0;$j < count($points);$j++){
			for($k = 0;$k < count($points);$k++){
				$dist = calc_dist($points[$j], $points[$k]);
				if(($dist < $nearest[$j]) && ($j != $k)){
					$nearest[$j] = $dist;
				}
			}
		}

		// 最も近いクラスでも$far_dist以上の距離がある場合、クローンセットから除外
		for($j = count($points) - 1;$j >= 0;$j--){
			if($nearest[$j] >= $far_dist){
				unset($list_clone[$i][$j]);
			}
			$list_clone[$i] = array_merge($list_clone[$i]);
		}
	}
	$list_clone = array_filter($list_clone);
	$list_clone = array_merge($list_clone);

	// -----------------------------------------------------
	// 共通クラスがあるクローンセットを連結
	// -----------------------------------------------------

	// 共通クラスがあるクローンを和集合にする
	for($i = 0;$i < count($list_clone);$i++){
		for($j = 0;$j < count($list_clone);$j++){
			if((array_intersect($list_clone[$i], $list_clone[$j]) == true) && ($i != $j)){
				for($k = 0;$k < count($list_clone[$j]);$k++){
					array_push($list_clone[$i], $list_clone[$j][$k]);
				}
				$list_clone[$i] = array_unique($list_clone[$i]);
				$list_clone[$i] = array_merge($list_clone[$i]);
				sort($list_clone[$i]);
			}
		}
	}

	// 重複を削除
	for($i = count($list_clone) - 1;$i >= 0;$i--){
		for($j = count($list_clone) - 1;$j >= 0;$j--){
			if((array_intersect($list_clone[$i], $list_clone[$j]) == true) && ($i != $j)){
				unset($list_clone[$i]);
				break;
			}
		}
		$list_clone = array_merge($list_clone);
	}

	// -----------------------------------------------------
	// XMLから内部クラスなどの余計なクラスを取り除く
	// -----------------------------------------------------

	// 関係ないので<cycles><packageCycles><packages>を削除
	unset($xml->cycles);
	unset($xml->packageCycles);
	unset($xml->packages);

	for($i = count($xml->classes->children()) - 1;$i >= 0;$i--){
		// innerClass="true"の<class>を削除
		if(($xml->classes->class[$i]->attributes()->innerClass) == "true"){
			unset($xml->classes->class[$i]);
		}
	}
	for($i = count($xml->classes->children()) - 1;$i >= 0;$i--){
		for($j = count($xml->classes->class[$i]->children()) - 1;$j >= 0;$j--){
			// nameに$を含む<classRef>を削除
			if(strstr(($xml->classes->class[$i]->classRef[$j]->attributes()->name), "$")){
				unset($xml->classes->class[$i]->classRef[$j]);
			}
			// type="usedBy"やtype="usesExternal"の<classRef>を削除
			else if(($xml->classes->class[$i]->classRef[$j]->attributes()->type) == "usedBy" || ($xml->classes->class[$i]->classRef[$j]->attributes()->type) == "usesExternal"){
				unset($xml->classes->class[$i]->classRef[$j]);
			}
		}
	}

	// XMLに存在するクラスのリスト$list_calssycleを作成
	for($i = 0;$i < count($xml->classes->children());$i++){
		$list_classycle[$i] = (string)$xml->classes->class[$i]->attributes()->name;
	}
	// $list_fileにあって$list_calssycleにないクラスのリスト$list_deleteを作成
	$list_delete = array_diff($list_file, $list_classycle);
	$list_delete = array_merge($list_delete);
	// $list_cloneに$list_deleteの要素が含まれていたらそのクローンセットは削除
	for($i = count($list_clone) - 1;$i >= 0;$i--){
		if(count(array_intersect($list_clone[$i], $list_delete)) > 0){
			unset($list_clone[$i]);
		}
	}
	$list_clone = array_merge($list_clone);

	// -----------------------------------------------------
	// 頂点を統合したXMLを得る
	// -----------------------------------------------------

	// 頂点統合前のXMLを保持
	$xml_before = clone $xml;

	// $list_cloneの内容に基づき、クラスを統合
	for($i = 0;$i < count($list_clone);$i++){
		// 統合する頂点の要素数の変数を作成
		$combine = $list_clone[$i];
		$num_of_combine = count($combine);

		// $combineの各要素は何番目の<class>なのか、配列$id_combineに格納
		$id_combine = array_fill(0, $num_of_combine, 0);
		for($j = 0;$j < $num_of_combine;$j++){
			$id_combine[$j] = 0;
			while(1){
				if($combine[$j] == ((string)$xml->classes->class[$id_combine[$j]]->attributes()->name)){
					break;
				}
				$id_combine[$j]++;
			}
		}

		// 頂点の名前を統合
		$combine_name = $combine[0];
		for($j = 0;$j < $num_of_combine - 1;$j++){
			$combine_name = $combine_name." + ".$combine[$j+1];
		}
		$xml->classes->class[$id_combine[0]]['name'] = $combine_name;

		// 利用している辺の追加
		for($j = 0;$j < ((count($id_combine))-1);$j++){
			for($k = 0;$k < (count($xml->classes->class[$id_combine[$j+1]]->children()));$k++){
				// 統合する頂点が持っている辺を元の頂点が持っているか調べる
				$search_used = $xml->classes->class[$id_combine[$j+1]]->classRef[$k]->attributes()->name;
				$id_search_used = 0;
				while($id_search_used < (count($xml->classes->class[$id_combine[0]]->children()))){
					if($search_used == ((string)$xml->classes->class[$id_combine[0]]->classRef[$id_search_used]->attributes()->name)){
						break;
					}
					$id_search_used++;
				}
				// 辺を持っていなければ要素を追加
				if($id_search_used == (count($xml->classes->class[$id_combine[0]]->children()))){
					$addNode = $xml->classes->class[$id_combine[0]]->addChild('classRef');
					$addNode['name'] = $search_used;
					$addNode['type'] = 'usesInternal';
				}
			}
		}

		// 統合した頂点を削除
		for($j = $num_of_combine;$j > 0;$j--){
			unset($xml->classes->class[$id_combine[$j]]);
		}
		// 統合したことで生まれた頂点を利用している頂点の<classRef>を書き換え
		for($j = 0;$j < count($xml->classes->children());$j++){
			// フラグをリセット
			$edge_flag = false;
			for($k = count($xml->classes->class[$j]->children()) - 1;$k >= 0;$k--){
				for($l = 0;$l < $num_of_combine;$l++){
					// $combineに含まれる<classRef>は統合後の名前に変更、フラグが立っている場合は辺が重複してしまうので削除
					if(($combine[$l] == ($xml->classes->class[$j]->classRef[$k]->attributes()->name)) && ($edge_flag == false)){
						$xml->classes->class[$j]->classRef[$k]['name'] = $combine_name;
						// 変更をした場合、フラグをオンに
						$edge_flag = true;
					}else if(($combine[$l] == ($xml->classes->class[$j]->classRef[$k]->attributes()->name)) && ($edge_flag == true)){
						unset($xml->classes->class[$j]->classRef[$k]);
					}
				}
			}
		}
	}

	// 統合によって生まれる自身を利用する辺の削除
	for($i = 0;$i < count($xml->classes->children());$i++){
		for($j = count($xml->classes->class[$i]->children()) - 1;$j >= 0;$j--){
			if(!strcmp(($xml->classes->class[$i]['name']), ($xml->classes->class[$i]->classRef[$j]['name']))){
				unset($xml->classes->class[$i]->classRef[$j]);
			}
		}
	}

	// -----------------------------------------------------
	// コンポーネントランク計算
	// -----------------------------------------------------

	// コンポーネントランクを計算する関数
	function calc_rank($xml){
		// クラス数
		$num_of_class = count($xml->classes->class);
		// コンポーネントランク計算用の配列を2つ用意
		$rank_old = array_fill(0, $num_of_class, (1 / $num_of_class));
		$rank_new = array_fill(0, $num_of_class, 0);

		// 何をもって収束と見るかの基準を計算
		// ここでは初期の均等なポイント×0.001としている
		$conv = (1 / $num_of_class) * 0.001;

		// 計算回数
		$calc_count = 0;

		// 収束するまで計算
		while(1){
			// $rank_newをリセット
			$rank_new = array_fill(0, $num_of_class, 0);
			// 計算回数をカウント
			$calc_count++;

			// ポイント分配の処理を全クラスおこなう
			for($i = 0;$i < $num_of_class;$i++){
				// type="usesInternal"のclassRefを検索し、$usesにオブジェクトとして格納
				$uses = $xml->classes->class[$i]->xpath('classRef [@type="usesInternal"]');
				// usesInternalの数
				$num_of_uses = count($uses);

				for($j = 0;$j < $num_of_uses;$j++){
					// 利用しているクラス名
					$used_by = $uses[$j]->attributes()->name;

					// 利用しているクラス名が出現するまでループを回し、配列の何番目のクラスなのかを$id_used_byに取得する
					$id_used_by = 0;
					while(1){
						if($used_by == ((string)$xml->classes->class[$id_used_by]->attributes()->name)){
							break;
						}
						$id_used_by++;
					}

					// 利用しているクラスにポイントを分配
					$rank_new[$id_used_by] = $rank_new[$id_used_by] + ($rank_old[$i] / $num_of_uses);
				}
			}

			// 算出された各ポイントを0.9倍する
			for($i = 0;$i < $num_of_class;$i++){
				$rank_new[$i] = $rank_new[$i] * 0.9;
			}

			// 失われたポイントを計算
			$loss = 1 - array_sum($rank_new);
			// 失われたポイントを全クラスに均等に分配
			for($i = 0;$i < $num_of_class;$i++){
				$rank_new[$i] = $rank_new[$i] + ($loss / $num_of_class);
			}

			// $rank_oldと$rank_newにおける、各クラスの差の絶対値の合計を求める
			$sum_diff_abs = 0;
			for($i = 0;$i < $num_of_class;$i++){
				$sum_diff_abs = $sum_diff_abs + abs($rank_old[$i] - $rank_new[$i]);
			}

			// 各クラスの差の絶対値の合計が$convを下回るとき、概ね収束したとみなして計算終了
			if($sum_diff_abs < $conv){
				break;
			}

			// $rank_oldに$rank_newを上書き
			$rank_old = $rank_new;
		}

		return $rank_new;
	}

	// 計算時間の計測開始
	$calc_time_start = microtime(true);

	// 計算
	$rank_before = calc_rank($xml_before);
	$rank = calc_rank($xml);

	// 計算時間の計測終了
	$calc_time_end = microtime(true);
	$calc_time = $calc_time_end - $calc_time_start;

	// -----------------------------------------------------
	// 評価値の順位
	// -----------------------------------------------------

	// 評価値の順位データを作成
	$sorted_rank_before = $rank_before;
	rsort($sorted_rank_before, SORT_NUMERIC);
	$sorted_rank = $rank;
	rsort($sorted_rank, SORT_NUMERIC);

	$num_of_sorted_rank = count($sorted_rank);
	for($i = 0;$i < $num_of_sorted_rank;$i++){
		// 統合前クラスの評価値を統合後クラスの評価値に複製
		if(strpos($xml->classes->class[$i]->attributes()->name, "+") == true){
			$copy_grade = array_search($rank[$i], $sorted_rank);
			// クラス名に含まれる「+」の数だけ複製
			for($j = 0;$j < (substr_count($xml->classes->class[$i]->attributes()->name, "+"));$j++){
				array_push($sorted_rank, $sorted_rank[$copy_grade]);
			}
		}
	}
	$sorted_rank = array_merge($sorted_rank);
	rsort($sorted_rank, SORT_NUMERIC);

	// -----------------------------------------------------
	// 結果表示
	// -----------------------------------------------------

	// 元々のXMLに存在するクラスのリスト$list_xml_beforeを作成
	for($i = 0;$i < count($xml_before->classes->children());$i++){
		$list_xml_before[$i] = (string)$xml_before->classes->class[$i]->attributes()->name;
	}

	// 各情報の出力
	echo("<div id=\"info\">\n");
	echo("解析したファイル: ".$file_class.", ".$file_clone."　");
	echo("クラス数: ".(count($rank_before))."　");
	echo sprintf("解析時間: %.5f 秒\n", $calc_time);
	echo("</div>\n");

	// 表の出力
	echo("<table summary=\"解析結果\" id=\"result\">\n");
	echo("<thead>\n");
	//echo("<th>Class Name</th><th>usedBy</th><th>usesIn</th><th>usesEx</th><th>Before</th><th>After</th><th>Up/Down</th><th>Rank</th>\n");
	echo("<th>Class Name</th><th>usesIn_before</th><th>usesIn_after</th><th>Before</th><th>After</th><th>Up/Down</th><th>Rank</th><th>Group</th>\n");
	echo("</thead>\n");
	echo("<tbody>\n");

	/*(6/12)********************************************************************/
	//Groupを入れる(6/12)
	$result_Group1 = array();//Group1を入れる
	$result_Group2 = array();//Group2を入れる
	/*先に最終的なグループを計算*/
	for($i = 0;$i < (count($xml->classes->class));$i++){
		if(strpos($xml->classes->class[$i]->attributes()->name, "+") == true){
			$combine_before = explode(" + ", $xml->classes->class[$i]->attributes()->name);
			for($j = 0;$j < count($combine_before);$j++){
				$key_before = array_search($combine_before[$j], $list_xml_before);
				for($k = 0;$k < (count($xml_before->classes->class[$key_before]->classRef));$k++){
					if($xml_before->classes->class[$key_before]->classRef[$k]->attributes()->type == "usesInternal"){
						$temp_string = (string)$xml_before->classes->class[$key_before]->classRef[$k]->attributes()->name;
						if(!in_array($temp_string,$result_Group1)){ //result_Groop1に属さない
							if(in_array($temp_string,$result_Group2)){
								$index = array_search($temp_string,$result_Group2);
								$Group2 = array_merge(array_slice($result_Group2, 0, $index),array_slice($result_Group2, $index + 1));
								array_push($result_Group1,$temp_string);
							}else array_push($result_Group2,$temp_string);
						}
					}
				}
			}
		}
	}
	/***************************************************************************/
	/***********************************************************************/
	/**add(5/27)
	/***********************************************************************/
	$str1_st = "<tr><td align=\"left\">";
	$str1_ed = "</td></tr>";
	$str2_st = "<tr><td class=\"comb\"　align=\"left\"><font color=\"#FF0000\">";
	$str2_ed = "</font></td></tr>";
	$group1_st = "<tr><td class=\"g1\" align=\"left\">";
	$group1_ed = "</td></tr>";
	$group2_st = "<tr><td class=\"g2\" align=\"left\">";
	$group2_ed = "</td></tr>";
	/***********************************************************************/

	for($i = 0;$i < (count($xml->classes->class));$i++){
		// 統合されたクラスかどうかで異なる表示
		if(strpos($xml->classes->class[$i]->attributes()->name, "+") == true){

/**************/
/*統合されている*/
/**************/
			// 統合されたクラスなら強調表示
			echo("<tr class=\"combined\">");
			echo("<td class=\"class_name\">".str_replace(" + ", "<br />", $xml->classes->class[$i]->attributes()->name)."</td>");
			// 統合されたクラスなので、統合される前のXMLから情報を取得
			$combine_before = explode(" + ", $xml->classes->class[$i]->attributes()->name);
			for($j = 0;$j < count($combine_before);$j++){
				$key_before = array_search($combine_before[$j], $list_xml_before);
				//$combine_used_by_before[$j] = $xml_before->classes->class[$key_before]->attributes()->usedBy;
				$combine_uses_in_before[$j] = $xml_before->classes->class[$key_before]->attributes()->usesInternal;	
				//$combine_uses_ex_before[$j] = $xml_before->classes->class[$key_before]->attributes()->usesExternal;
				$combine_rank_before[$j] = $rank_before[$key_before];
			}
			//echo("<td>".implode("<br />", $combine_used_by_before)."</td>");
			//echo("<td>".implode("<br />", $combine_uses_in_before)."</td>");
			echo("<td>");
			
			/***********************************************/
			/*グループ分け*/
			$Group1 = array();//Group1を入れる
			$Group2 = array();//Group2を入れる
			for($j = 0;$j < count($combine_before);$j++){
				$key_before = array_search($combine_before[$j], $list_xml_before);
				for($k = 0;$k < (count($xml_before->classes->class[$key_before]->classRef));$k++){
					if($xml_before->classes->class[$key_before]->classRef[$k]->attributes()->type == "usesInternal")
						/*********************************/
						/*add(6/9)*/
						$temp_string = (string)$xml_before->classes->class[$key_before]->classRef[$k]->attributes()->name;
						if(!in_array($temp_string,$Group1)){ //Groop1に属さない
							if(in_array($temp_string,$Group2)){
								$index = array_search($temp_string,$Group2);
								$Group2 = array_merge(array_slice($Group2, 0, $index),array_slice($Group2, $index + 1));
								array_push($Group1,$temp_string);
							}
							else array_push($Group2,$temp_string);
						}
						/*add(6/9)*/
						/*********************************/
				}
			}
			/***********************************************/
			
			for($j = 0;$j < count($combine_before);$j++){
				echo($combine_uses_in_before[$j]);
				echo("<table class=\"usesIn\">");
				$key_before = array_search($combine_before[$j], $list_xml_before);
				/**debug***************
				echo("<br>【combine_before[j]=".$combine_before[$j]."】");
				echo (" 【key=".$key_before."】");
				/**********************/
				for($k = 0;$k < (count($xml_before->classes->class[$key_before]->classRef));$k++){
					if($xml_before->classes->class[$key_before]->classRef[$k]->attributes()->type == "usesInternal"){
						$temp_string = $xml_before->classes->class[$key_before]->classRef[$k]->attributes()->name;
						if(in_array($temp_string,$Group1)){//group1に属する
							echo($group1_st.$temp_string.$group1_ed);
						}else if(in_array($temp_string,$Group2)){//group2に属する
							echo($group2_st.$temp_string.$group2_ed);
						}else{
							echo($str1_st.$temp_string.$str1_ed);
						}
					}
				}
				echo("</table>");
			}
			/***********************************************************************/
			/*add(6/9)*
			echo("<table class=\"usesIn\">");
			for($g1=0;$g1<count($Group1);$g1++){
				echo($group1_st.$Group1[$g1].$group1_ed);
			}
			for($g2=0;$g2<count($Group2);$g2++)
				echo($group2_st.$Group2[$g2].$group2_ed);
			echo("</table>");
			/*add(6/9)*/
			/***********************************************************************/
			echo("</td>");
			/***********************************************************************/
			/**add(5/28)
			/***********************************************************************/
			echo("<td>");
		  echo($xml->classes->class[$i]->attributes()->usesInternal);
			echo("<table class=\"usesIn\">");
			for($j = 0;$j < (count($xml->classes->class[$i]->classRef));$j++){
				if($xml->classes->class[$i]->classRef[$j]->attributes()->type == "usesInternal")
					if (strpos($xml->classes->class[$i]->classRef[$j]->attributes()->name, "+") === FALSE){
						$temp_string = $xml->classes->class[$i]->classRef[$j]->attributes()->name;
						if(in_array($temp_string,$Group1)){//group1に属する
							echo($group1_st.$temp_string.$group1_ed);
						}else if(in_array($temp_string,$Group2)){//group2に属する
							echo($group2_st.$temp_string.$group2_ed);
						}else{
							echo($str1_st.$temp_string.$str1_ed);
						}
				}else{
						echo($str2_st.str_replace("+", $str2_ed.$str2_st,$xml->classes->class[$i]->classRef[$j]->attributes()->name).$str2_ed);
				}
			}
			echo("</table>");
			echo("</td>");
			/***********************************************************************/
			/**add(5/28)
			/***********************************************************************/
			
			
			//echo("<td>".implode("<br />", $combine_uses_ex_before)."</td>");
			
			echo("<td>");
			for($j = 0;$j < count($combine_rank_before);$j++){
				echo sprintf("%.5f", $combine_rank_before[$j]);
				if($j != (count($combine_before) - 1)){
					echo "<br />";
				}
			}
			echo("</td>");
			echo sprintf("<td>%.5f</td>", $rank[$i]);

			// 評価値の変化を表示
			echo("<td>-</td>");

			// 評価値の順位の変化を表示
			// 順位 + ((同一順位の部品数 - 1) * 0.5)
			echo("<td>");
			for($j = 0;$j < count($combine_rank_before);$j++){
				$grade = array_search($rank[$i], $sorted_rank) + 1 + ((count(array_keys($sorted_rank, $rank[$i])) - 1) * 0.5);
				$grade_before = array_search($combine_rank_before[$j], $sorted_rank_before) + 1 + ((count(array_keys($sorted_rank_before, $combine_rank_before[$j])) - 1) * 0.5);
				if($grade < $grade_before){
					echo("<span class=\"rank_up\">".$grade_before."→".$grade." ↑</span>");
				}else if($grade == $grade_before){
					echo("<span class=\"rank_unchanged\">".$grade_before."→".$grade." →</span>");
				}else{
					echo("<span class=\"rank_down\">".$grade_before."→".$grade." ↓</span>");
				}
				if($j != (count($combine_before) - 1)){
					echo "<br />";
				}
			}
			echo("</td>");
			
			/**********************************************/
			/*グループの表示(6/12)*/
			echo("<td>-</td>");
			/**********************************************/
			
			
			// この統合されたクラスの情報を破棄
			unset($combine_before, $combine_used_by_before, $combine_uses_in_before, $combine_uses_ex_before, $combine_rank_before);

		}else{
			
/****************/
/*統合されていない*/
/***************/
			echo("<tr>");
			echo("<td class=\"class_name\">".$xml->classes->class[$i]->attributes()->name."</td>");
			//echo("<td>".$xml->classes->class[$i]->attributes()->usedBy."</td>");
			
			/***********************************************************************/
			/**add(5/28)
			/***********************************************************************/
			$str1_st = "<tr><td align=\"left\">";
			$str1_ed = "</tr></td>";
			$str2_st = "<tr><td align=\"left\"><font color=\"#FF0000\">";
			$str2_ed = "</font></td></tr>";
			/***********************************************************************/
			echo("<td>");
		  echo($xml->classes->class[$i]->attributes()->usesInternal);
			echo("<table class=\"usesIn\">");
			$not_combine_before = (string)$xml->classes->class[$i]->attributes()->name;
			/****************
			echo("<br />".$not_combine_before);
			/****************/
			$key_before = array_search($not_combine_before, $list_xml_before);
			for($k = 0;$k < (count($xml_before->classes->class[$key_before]->classRef));$k++){
				if($xml_before->classes->class[$key_before]->classRef[$k]->attributes()->type == "usesInternal")
					echo($str1_st.$xml_before->classes->class[$key_before]->classRef[$k]->attributes()->name.$str1_ed);
			}
			echo("</table>");
			echo("</td>");
			/***********************************************************************/
			/**add(5/27)
			/***********************************************************************/
			echo("<td>");
		  echo($xml->classes->class[$i]->attributes()->usesInternal);
			echo("<table class=\"usesIn\">");
			for($j = 0;$j < (count($xml->classes->class[$i]->classRef));$j++){
				if($xml->classes->class[$i]->classRef[$j]->attributes()->type == "usesInternal")
					if (strpos($xml->classes->class[$i]->classRef[$j]->attributes()->name, "+") === FALSE){
						echo($str1_st.$xml->classes->class[$i]->classRef[$j]->attributes()->name.$str1_ed);
				}else{
						echo($str2_st.str_replace("+", $str2_ed.$str2_st,$xml->classes->class[$i]->classRef[$j]->attributes()->name).$str2_ed);
					}
			}
			echo("</table>");
			echo("</td>");
			/***********************************************************************/
			/**add(5/27)
			/***********************************************************************/
			
	
			//echo("<td>".$xml->classes->class[$i]->attributes()->usesExternal."</td>");
			$key_before = array_search($xml->classes->class[$i]->attributes()->name, $list_xml_before);
			echo sprintf("<td>%.5f</td>", $rank_before[$key_before]);
			echo sprintf("<td>%.5f</td>", $rank[$i]);

			// 変動率の定義に従い，評価値の変化を表示
			$rate = (( ($rank[$i] * count($rank)) / ($rank_before[$key_before] * count($rank_before)) ) ) * 100;

			if($rate > 100){
				echo sprintf("<td class=\"rank_up\">%.1f%%</td>", $rate);
			}else if($rate == 0){
				echo sprintf("<td class=\"rank_unchanged\">%.1f%%</td>", $rate);
			}else{
				echo sprintf("<td class=\"rank_down\">%.1f%%</td>", abs($rate));
			}

			// 評価値の順位の変化を表示
			// 順位 + ((同一順位の部品数 - 1) * 0.5)
			$grade = array_search($rank[$i], $sorted_rank) + 1 + ((count(array_keys($sorted_rank, $rank[$i])) - 1) * 0.5);
			$grade_before = array_search($rank_before[$key_before], $sorted_rank_before) + 1 + ((count(array_keys($sorted_rank_before, $rank_before[$key_before])) - 1) * 0.5);

			if($grade < $grade_before){
				echo("<td class=\"rank_up\">".$grade_before."→".$grade." ↑</td>");
			}else if($grade == $grade_before){
				echo("<td class=\"rank_unchanged\">".$grade_before."→".$grade." →</td>");
			}else{
				echo("<td class=\"rank_down\">".$grade_before."→".$grade." ↓</td>");
			}
			
			/**********************************************/
			/*グループの表示(6/12)*/
			$temp_string = $xml->classes->class[$i]->attributes()->name;
			if(in_array($temp_string,$result_Group1)){//group1に属する
				echo("<td class=\"g1\">1</td>");
			}else if(in_array($temp_string,$result_Group2)){//group2に属する
				echo("<td class=\"g2\">2</td>");
			}else{
				echo("<td class=\"g3\">3</td>");
			}
			/**********************************************/
		}
		echo("</tr>\n");
	}
	echo("</tbody>\n");
	echo("</table>\n");

	/***********************************************************************/
	/*add(6/12)*
	echo("<table class=\"usesIn\">");
	for($g1=0;$g1<count($result_Group1);$g1++){
		echo($group1_st.$result_Group1[$g1].$group1_ed);
	}
	for($g2=0;$g2<count($result_Group2);$g2++)
		echo($group2_st.$result_Group2[$g2].$group2_ed);
	echo("</table>");
	/*add(6/12)*/
	/***********************************************************************/
?>
</div>
<div id="footer">
<p id="copyright">Yokomori Lab.</p>
</div>
</div>
</body>
</html>