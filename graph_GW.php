<?php echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>"; ?> 
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="ja" xml:lang="ja">
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<title>Component Rank Calculator Graph</title>
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
<div id="header"><h1>Component Rank Calculator Graph</h1></div>
<div id="main">
<?php
	// -----------------------------------------------------
	// 設定
	// -----------------------------------------------------

	// Classycleファイルの設定
//	$file_class = "xml/jmp.xml";
//	$file_class = "xml/junit.xml";
//	$file_class = "xml/classycle.xml";
//	$file_class = "xml/jgraphx.xml";
	$file_class = "xml/rabbit.xml";
//	$file_class = "xml/mantissa.xml";

	// CCFinderファイルの設定
	////$file_clone = "clone/jmp.txt";
//	$file_clone = "clone/junit.txt";
//	$file_clone = "clone/classycle.txt";
//	$file_clone = "clone/jgraphx.txt";
	$file_clone = "clone/rabbit.txt";
//	$file_clone = "clone/mantissa.txt";

	// 「遠い」と判断するクラス間の距離の定義（これ「以上」だと遠いと判断）
	$far_dist = 4;

	// 実行環境の設定
	ini_set('memory_limit' ,'512M');
	ini_set('max_execution_time' ,'120');

	// 計算時間の計測開始
	$calc_time_start = microtime(true);
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

	// 計算時間の計測終了
	$calc_time_end = microtime(true);
	$calc_time = $calc_time_end - $calc_time_start;
?>
<?php
	// -----------------------------------------------------
	// 結果表示
	// -----------------------------------------------------

	// 元々のXMLに存在するクラスのリスト$list_xml_beforeを作成
	for($i = 0;$i < count($xml->classes->children());$i++){
		$list_xml[$i] = (string)$xml->classes->class[$i]->attributes()->name;
	}

	// 各情報の出力
	echo("<div id=\"info\">\n");
	echo("解析したファイル: ".$file_class.", ".$file_clone."　");
	echo("クラス数: ".count($xml->classes->class)."　");
	echo sprintf("解析時間: %.5f 秒 ", $calc_time);
	echo("キャンバスサイズ<span id=canvas_size></span>\n");
	echo("</div>\n");

	//同じパッケージのものか分ける
	$classgroup = array();//パッケージ名
	$classgroup_number = array();//ノードごとのパッケージの番号
	$node = array();
	for($i = 0;$i < count($xml->classes->children());$i++)
			array_push($node,(string)$xml->classes->class[$i]->attributes()->name);
	for($i =0;$i < count($node);$i++){
		$temp = "";
		$str_group = explode(".", $node[$i]);
		for($j=0;$j < count($str_group)-1;$j++){
			$temp = $temp.".".$str_group[$j];
		}
		$group_flag = false; 
		for($j= 0;$j < count($classgroup);$j++){
			if($temp == $classgroup[$j]){
				$classgroup_number[$i] = $j;
				$group_flag = true; 
			}
		}
		if(!$group_flag){
				$classgroup[count($classgroup)] = $temp;
				$classgroup_number[$i] = count($classgroup)-1;
		}
	}
?>

<div id="container">
	<canvas id="canvas" width="1000" height="1000"></canvas>
</div>
<!-- script src="http://ajax.googleapis.com/ajax/libs/jquery/1.10.1/jquery.min.js\"></script -->
<script type="text/javascript" src="js/graph.js"></script>
<script type="text/javascript">
(function($) {
	var $canvas, canvas, center, random, max_num;
	Cnodes = [];//クラスタリングをするノードの保管
	point = [];//座標の文字列
	random = function(max) {return ~~(Math.random() * max);};
	WIN_W = window.innerWidth;
	WIN_H = window.innerHeight;
	$canvas = $('#canvas');
	$canvas.attr({'width': WIN_W,'height': WIN_H});
	canvas = new Canvas($canvas[0]);
	center = {x:canvas.width/2,y:canvas.height/2};
	MouseInit();
	document.getElementById("canvas_size").innerHTML = "("+WIN_W +"x"+ WIN_H+")";

	<?php
		//一番internalの多いものを探す
		$internal_max=0;$max_class=0;
		for($i = 0;$i < count($xml->classes->children());$i++){
			if($internal_max < $xml->classes->class[$i]->attributes()->usesInternal){
				$internal_max = (int)$xml->classes->class[$i]->attributes()->usesInternal;
				$max_class = $i;
			}
		}	

		echo("nodes = [\n");
		$node = array();
		$node_branch = array();
		for($i = 0;$i < count($xml->classes->children());$i++){
				array_push($node,(string)$xml->classes->class[$i]->attributes()->name);
				array_push($node_branch,$xml->classes->class[$i]->attributes()->usesInternal);
				//if($i==$max_class){
				if($i==0){
					echo(
						"new Node(
							\"$i\",
							center.x,
							center.y,
							$classgroup_number[$i]
						),\n");
				}else{
					echo(
						"new Node(
							\"$i\",
							center.x - 250 + random(500),
							center.y - 250 + random(500),
							$classgroup_number[$i]
						),\n");
				}
		}
		echo("];\n");
		echo("graph = new ForceDirectedGraph(nodes);\n");
	/**************************/
	for($i = 0;$i < count($node);$i++){
			$count=count($xml->classes->class[$i]->classRef);
			for($k = 0;$k < count($xml->classes->class[$i]->classRef);$k++){
					$connect = (string)$xml->classes->class[$i]->classRef[$k]->attributes()->name;
					$key = array_search($connect, $node);
					echo("graph.connect($i, $key);\n");
			}
	}
	?>
	canvas.add(graph);
		return setInterval(function() {
			graph.balance(<?php $max_class ?>);
			canvas.clear().fill('white');
			canvas.Cdraw();
			canvas.draw();
		}, 5);
	})(jQuery);
	</script>
<form>
	<input class="clustering" type="button" value="クラスタリング" onClick="ClusteringInit()"><br>
	<table class="option">
		<tr>
			<td>クーロン力(こちらで調整)</td><td><span id="coulomb"></span></td>
			<td><input type="button" value="＋" onClick="graph.coulomb_add(true)"></td>
			<td><input type="button" value="－" onClick="graph.coulomb_add(false)"></td>
		</tr>
		<tr>
			<td>バネ定数</td><td><span id="bounce"></span></td>
			<td><input type="button" value="＋" onClick="graph.bounce_add(true)"></td>
			<td><input type="button" value="－" onClick="graph.bounce_add(false)"></td>
		</tr>
	</table>
</form><br>
<div id="clustering"></div>
<script language="JavaScript">
	clustering_str = document.getElementById("clustering");
	coulomb_str = document.getElementById("coulomb");
	bounce_str = document.getElementById("bounce");
</script>
<?php
	echo("<table>");
	echo("<tr><td>番号</td><td>パス</td><td>internal数</td><td onClick=\"colordisplay()\">パッケージ</td><td>座標</td></tr>");
	for($i = 0;$i < count($node);$i++){
		echo("<tr>");
			echo("<td>".$i."</td>");
			echo("<td class=\"class_name\">".$node[$i]."</td>");
			echo("<td>".$node_branch[$i]."</td>");
			echo("<td class=\"class_name\" id=\"col$i\" onClick=\"colordisplay($classgroup_number[$i])\">".$classgroup_number[$i].$classgroup[$classgroup_number[$i]]."</td>");
			echo("<td id=\"point$i\"></td>");
		echo("</tr>");
	}
	echo("</table>");
	for($i=0;$i<count($node);$i++){
		$num = $classgroup_number[$i]%12;
		echo("
			<script language=\"JavaScript\">
				point[$i] = document.getElementById(\"point$i\");
				document.getElementById(\"col$i\").style.backgroundColor = colorPalette[".$num."];
				document.getElementById(\"col$i\").style.color = \"#FFF\";
			</script>");
	}
?>
</div>
<div id="footer">
<p id="copyright">Yokomori Lab.</p>
</div>
</div>
</body>
</html>
