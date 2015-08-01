var Canvas, ForceDirectedGraph, Node, MouseMove, CNode ,clusteringGraph;

//======================================================
//グローバル変数
//======================================================
colorPalette = ['#F39800','#EA5504',"#E6002D","#B70171","#36318F","#009E96","#009944","#801E6C","#F39800","#A40000","#5D310C","#0068B7"];

//======================================================
//キャンバスクラス
//======================================================
Canvas = (function() {
  function Canvas(el) {
    this.ctx = el.getContext('2d');
    this.width = el.width;
    this.height = el.height;
    this.elements = [];
  }

  Canvas.prototype.add = function(element) {
    this.elements.push(element);
    return this;
  };

  Canvas.prototype.keep = function(fn) {
    this.ctx.save();
    this.ctx.beginPath();
    fn();
    this.ctx.closePath();
    return this.ctx.restore();
  };

  Canvas.prototype.clear = function() {
    this.ctx.clearRect(0, 0, this.width, this.height);
    return this;
  };

  Canvas.prototype.fill = function(bg) {
    if (bg == null)bg='#000';
    this.keep((function(_this) {
      return function() {
        _this.ctx.fillStyle = bg;
        return _this.ctx.fillRect(0, 0, _this.width, _this.height);
      };
    })(this));
    return this;
  };

  Canvas.prototype.circle = function(x, y, r, bg) {
    if (bg == null)bg = '#000';
    return this.keep((function(_this) {
      return function() {
        _this.ctx.fillStyle = bg;
        _this.ctx.arc(x, y, r, 0, 2 * Math.PI);
        return _this.ctx.fill();
      };
    })(this));
  };

  Canvas.prototype.line = function(fromX, fromY, toX, toY, color, width) {
    if (color == null)color='#999';
    if (width == null)width = 2;
		return this.keep((function(_this) {
      return function() {
        _this.ctx.strokeStyle = color;
        _this.ctx.strokeWidth = width;
        _this.ctx.moveTo(fromX, fromY);
        _this.ctx.lineTo(toX, toY);
        return _this.ctx.stroke();
      };
    })(this));
  };

  Canvas.prototype.text = function(txt, x, y, color, font) {
    if (color == null)color = 'white';
    if (font == null)font = '12px bold';
		
    return this.keep((function(_this) {
      return function() {
        _this.ctx.font = font;
        _this.ctx.textAlign = 'center';
        _this.ctx.textBaseline = 'middle';
        _this.ctx.fillStyle = color;
        return _this.ctx.fillText(txt, x, y);
      };
    })(this));
  };

  Canvas.prototype.draw = function() {
    var k, len, ref;
    for (k = 0; k < this.elements.length; k++) {
      if (this.elements[k].constructor === ForceDirectedGraph)
				this._drawGraph(this.elements[k]);
				this.drawpoint(this.elements[k]);
				this.drawaxis();
    }
    return;
  };
	
	//クラスタリング時の出力
	Canvas.prototype.Cdraw = function() {
    var k, len, ref;
    for (k = 0; k < Cnodes.length; k++) {
			if(!Cnodes[k].init){
				if(Cnodes[k].num<max_num*1/4)rgb_str='#CCC';
				else if(Cnodes[k].num<max_num*2/4)rgb_str='#999';
				else if(Cnodes[k].num<max_num*3/4)rgb_str='#666';
				else if(Cnodes[k].num<max_num*4/4)rgb_str='#333';
				else rgb_str = '#F66';
				this.circle(Cnodes[k].x, Cnodes[k].y, Cnodes[k].r,rgb_str);
				this.text(Cnodes[k].value, Cnodes[k].x, Cnodes[k].y);
			}
    }
    return;
  };

  Canvas.prototype._drawGraph = function(graph) {
    var connected, i, j, node;
    connected = [];
    for ( i=0;i<=graph.nodes.length;i++)connected[i]=[];
    for ( i=0;i<graph.nodes.length;i++) {
      node = graph.nodes[i];
      for ( j=0;j<node.connections.length;j++) {
        if (connected[i][j] || connected[j][i])continue;
        connected[i][j] = true;
				connected[j][i] = true;
        this.line(node.x, node.y, node.connections[j].x, node.connections[j].y);
      }
    }
		//フォーカスで線に色付け(赤色)
		for ( i=0;i<graph.nodes.length;i++) {
      node = graph.nodes[i];
      for ( j=0;j<node.connections.length;j++) {
				if((i==move_flag)&&move_flag!=0)
					this.line(node.x, node.y, node.connections[j].x, node.connections[j].y,'#F00');
      }
    }
    for ( i=0; i<graph.nodes.length; i++) {
      node = graph.nodes[i];
      if (node.isFocus)this.circle(node.x, node.y, node.r + 5, '#444');
      if(node.colorflag)this.circle(node.x, node.y, node.r, node.background);else this.circle(node.x, node.y, node.r, '#CCC');
      this.text(node.value, node.x, node.y);
    }
    return;
  };
	
	//座標情報の表示
	Canvas.prototype.drawpoint = function(graph) {
		var centerX = parseInt(graph.nodes[0].x);
		var centerY = parseInt(graph.nodes[0].y);
		for ( i=0;i<graph.nodes.length;i++) {
      node = graph.nodes[i];
			//point[i].innerHTML = "("+(parseInt(node.x)-centerX)+","+(parseInt(node.y)-centerY)+")";
			point[i].innerHTML = "("+parseInt(node.x)+","+parseInt(node.y)+")";
			//端っこは背景色を赤
			if(node.edge())
				point[i].style.backgroundColor ='#faa';
			else
				point[i].style.backgroundColor ='#eee';
		}
		return;
  };
	
	//軸の表示
	Canvas.prototype.drawaxis = function() {
		var wid = this.width;
    var hei = this.height;
		for(i=1;i<(wid/100);i++){
			this.line( 100*i, 0, 100*i, 20, '#CCC');
			this.text(100*i, 100*i, 20, '#999');
		}
		for(i=1;i<(hei/100);i++){
			this.line( 0, 100*i, 20, 100*i, '#CCC');
			this.text(100*i, 20, 100*i, '#999');
		}
		this.line( 0, 0, 0, hei, '#CCC');
		this.line( 0, hei, wid, hei, '#CCC');
		this.line( wid, 0, wid, hei, '#CCC');
		this.line( 0, 0, wid, 0, '#CCC');
		return;
  };
	
  return Canvas;
})();

//======================================================
//ノードクラス
//======================================================
Node = (function() {

  Node._lastID = 0;

  function Node(value, x1, y1, package, r1) {
    var idx;
    this.value = value;
    this.x = x1;
    this.y = y1;
		this.package = package;//パッケージ番号
    this.background = colorPalette[package % colorPalette.length];
    this.r = r1 != null ? r1 : 10;
    this.id = Node._lastID++;
    this.connections = [];//繋がっているノード
    this.vx = 0;
    this.vy = 0;
		this.colorflag = true;//色の表示
		
    idx = ~~(Math.random() * colorPalette.length);//色の選択
    if (!this.background) {
      this.background = colorPalette[idx];
    }
  }

  Node.prototype.connect = function(node) {
    return this.connections.push(node);
  };
    
  Node.prototype.connect_count = function() {
    return this.connections.length;
  };

  Node.prototype.focus = function() {
    return this.isFocus = true;
  };

  Node.prototype.blur = function() {
    return this.isFocus = false;
  };
	
	//端っこにあるかを判定
	Node.prototype.edge = function() {
		if(this.x-this.r<=0||this.y-this.r<=0||WIN_W<=this.x+this.r||WIN_H<=this.y+this.r)
			 return true;
		else return false;
  };
	
	
	Node.prototype.getX = function() { return this.x; };
	Node.prototype.getY = function() { return this.y; };
	Node.prototype.setX = function(pointX) { this.x=pointX; };
	Node.prototype.setY = function(pointY) { this.y=pointY; };
	
	return Node;
})();

//======================================================
//演算クラス
//======================================================
ForceDirectedGraph = (function() {
  ForceDirectedGraph.BOUNCE = 0.03;//バネ定数(BOUNCE < 0.1[推奨])
  ForceDirectedGraph.ATTENUATION = 0.7;//減衰定数(ATTENUATION < 1[必須])
  ForceDirectedGraph.COULOMB = 20;//クーロン数

  function ForceDirectedGraph(nodes) {
    this.nodes = nodes;　
  }

  ForceDirectedGraph.prototype.add = function(node) {
    return this.nodes.push(node);
  };

  ForceDirectedGraph.prototype.connect = function(a, b) {
		//this.nodes[a].r--;
		//this.nodes[b].r++;
    this.nodes[a].connect(this.nodes[b]);
    return this.nodes[b].connect(this.nodes[a]);
  };

  ForceDirectedGraph.prototype.balance = function(max) {
    var distX, distY, fx, fy, i, number, targetNode;
    for (number=0;number<this.nodes.length;number++) {
      targetNode = this.nodes[number];
      if (targetNode.isFocus)continue;
      fx = 0;
      fy = 0;
			/*クーロン力*/
      for (i=0;i<this.nodes.length;i++) {
        if (targetNode === this.nodes[i])continue;
				distX = targetNode.x - this.nodes[i].x;
        distY = targetNode.y - this.nodes[i].y;
        rsq = distX * distX + distY * distY;
        fx += ForceDirectedGraph.COULOMB * distX / rsq;
        fy += ForceDirectedGraph.COULOMB * distY / rsq;
      }
			/*バネ*/
      for (i=0;i<targetNode.connections.length;i++) {
        distX = targetNode.connections[i].x - targetNode.x;
        distY = targetNode.connections[i].y - targetNode.y;
        fx += ForceDirectedGraph.BOUNCE * distX;
        fy += ForceDirectedGraph.BOUNCE * distY;
      }
			/*力(fx,fy)を速度に変換*/
      targetNode.vx = (targetNode.vx + fx) * ForceDirectedGraph.ATTENUATION;
      targetNode.vy = (targetNode.vy + fy) * ForceDirectedGraph.ATTENUATION;
			/*maxの場合は変化させない*/
      if (number !== max) {
        targetNode.x += targetNode.vx;
        targetNode.y += targetNode.vy;
				//後で端っこのは動かない処理
				if(targetNode.x-targetNode.r<=0)targetNode.x = targetNode.r;
				if(targetNode.y-targetNode.r<=0)targetNode.y = targetNode.r;
				if(WIN_W<=targetNode.x+targetNode.r)targetNode.x = WIN_W-targetNode.r;
				if(WIN_H<=targetNode.y+targetNode.r)targetNode.y = WIN_H-targetNode.r;
      }
			/*クーロン力の出力*/
			coulomb_str.innerHTML = ForceDirectedGraph.COULOMB;
			bounce_str.innerHTML = ForceDirectedGraph.BOUNCE;
    }
    return;
  };
	
	//クーロン力の変化
	ForceDirectedGraph.prototype.coulomb_add = function(bool) {
		if(bool)
    	return ForceDirectedGraph.COULOMB += 1;
		else if(ForceDirectedGraph.COULOMB>1)
    	return ForceDirectedGraph.COULOMB -= 1;
		
  };
	
	//バネ力の変化
	ForceDirectedGraph.prototype.bounce_add = function(bool) {
		ForceDirectedGraph.BOUNCE *= 100;
		if(bool)
    	ForceDirectedGraph.BOUNCE += 1;
		else if(ForceDirectedGraph.BOUNCE>1)
    	ForceDirectedGraph.BOUNCE -= 1;
			return ForceDirectedGraph.BOUNCE = Math.round(ForceDirectedGraph.BOUNCE)/ 100;
  };
	
	
  return ForceDirectedGraph;
})();

//======================================================
//マウス動作
//======================================================
function MouseMoveFunc(e){
	var rect =  document.getElementById("canvas").getBoundingClientRect();
	mouseX = parseInt(e.clientX - rect.left);
	mouseY = parseInt(e.clientY - rect.top);
	if(document.getElementById("mouseX")!=null)
		document.getElementById("mouseX").innerHTML = mouseX;
	if(document.getElementById("mouseY")!=null)
		document.getElementById("mouseY").innerHTML = mouseY;
}
function MouseDownFunc(e){
	for(i=0;i<nodes.length;i++){
		var diffX = parseInt(nodes[i].getX())-mouseX;diffX *= diffX;
		var diffY = parseInt(nodes[i].getY())-mouseY;diffY *= diffY;
		if(diffX+diffY<20*20)move_flag = i;
	}
}
function MouseUpFunc(e){move_flag = 0;}
function MouseInit(){
	move_flag = 0;
	setInterval(function() {
		if(move_flag!=0){
			nodes[move_flag].setX(mouseX);
			nodes[move_flag].setY(mouseY);
		}
	}, 20);
	if(document.addEventListener){//イベントリスナーに対応している
		document.addEventListener("mousemove" , MouseMoveFunc);
		document.getElementById("canvas").addEventListener("mousedown",MouseDownFunc);//押下
		document.getElementById("canvas").addEventListener("mouseup",MouseUpFunc);//押上
	}else if(document.attachEvent){
		document.attachEvent("onmousemove" , MouseMoveFunc);
	}
}
//======================================================
//その他動作
//======================================================
function colordisplay(numbar){
	if(numbar==null){
		for(i=0;i<nodes.length;i++)
			nodes[i].colorflag = true;
		return 0;
	}
	for(i=0;i<nodes.length;i++){
		if(nodes[i].package == numbar)
			nodes[i].colorflag = true;
		else
			nodes[i].colorflag = false;
	}
}
//======================================================
//クラスタリング
//======================================================
CNode = (function() {
  CNode._lastID = 0;
  function CNode(value, x1, y1) {
    this.value = value;
    this.x = x1;
    this.y = y1;
    this.r = 10;
    this.id = Node._lastID++;
		this.num = 1;//クラスタリングした時にまとめたクラスの数
		this.exist = true;
		this.init = true;
		this.min = null;//一番小さいクラス
		
		if(this.x-this.r<=0||this.y-this.r<=0||WIN_W<=this.x+this.r||WIN_H<=this.y+this.r)
			this.exist = false;
  }
	CNode.prototype.getX = function() { return this.x; };
	CNode.prototype.getY = function() { return this.y; };
	CNode.prototype.setX = function(pointX) { this.x=pointX; };
	CNode.prototype.setY = function(pointY) { this.y=pointY; };
	
	return CNode;
})();

clusteringGraph = (function() {
	connectA = [];//くっついたノード
	connectB = [];//くっついたノード
	connect_len = [];//くっついたノードの距離
	
  function clusteringGraph(nodes) {
		this.nodes = nodes;
		max_num = this.nodes.length;
		for(i=0;i<this.nodes.length;i++)
			this.nodes[i].min = i;
	}
	
  clusteringGraph.prototype.calc = function() {
		connectA.length=0;
		connectB.length=0;
		connect_len.length=0;
		
		while(this.exist_count()>1){
			min_length = null;
			for(i=0;i<this.nodes.length;i++){
				if(!this.nodes[i].exist)continue;
				for(j=i+1;j<this.nodes.length;j++){
					if(!this.nodes[j].exist)continue;
					dist = calcDistance(this.nodes[i].x,this.nodes[i].y,this.nodes[j].x,this.nodes[j].y);
					if(min_length==null || min_length > dist ){
						min_length = dist;
						min_nodeA = i;
						min_nodeB = j;
					}
				}
			}
			connectA.push(this.nodes[min_nodeA].min);
			connectB.push(this.nodes[min_nodeB].min);
			connect_len.push(min_length);
			this.nodes.push(new CNode(
				this.nodes[min_nodeA].value+","+this.nodes[min_nodeB].value,
				(this.nodes[min_nodeA].x+this.nodes[min_nodeB].x)/2,
				(this.nodes[min_nodeA].y+this.nodes[min_nodeB].y)/2
			));
			this.nodes[this.nodes.length-1].num = this.nodes[min_nodeA].num+this.nodes[min_nodeB].num;
			this.nodes[this.nodes.length-1].r = 10+this.nodes[this.nodes.length-1].num*1;
			this.nodes[this.nodes.length-1].init = false;
			if(this.nodes[min_nodeA].min<this.nodes[min_nodeB].min)
				this.nodes[this.nodes.length-1].min = this.nodes[min_nodeA].min;
			else
				this.nodes[this.nodes.length-1].min = this.nodes[min_nodeB].min;
			this.nodes[min_nodeA].exist = false;
			this.nodes[min_nodeB].exist = false;
		}
		clustering_str.innerHTML = "";
		for(i=0;i<connectA.length;i++)
			clustering_str.innerHTML += "["+connectA[i]+"-"+connectB[i]+"="+connect_len[i]+"] ";
  };
	
	//クラスタリングして得た結果をXMLに変換
	clusteringGraph.prototype.ConvertXML = function() {
		//XML用のActiveXObjectの生成
		var doc =　new ActiveXObject("Msxml2.DOMDocument.3.0");
		var node =　doc.createProcessingInstruction("xml", "version=\"1.0\" encoding=\"UTF-8\" ");
		doc.appendChild(node);
		var rootNode =　doc.createElement("ClusteringNode");
		doc.appendChild(rootNode);
		for(i=0;i<connectA.length;i++){
			var elt = doc.createElement("row");
			var att1 = doc.createAttribute("A");att1.text = connectA[i];elt.attributes.setNamedItem(att1);
			var att2 = doc.createAttribute("B");att2.text = connectB[i];elt.attributes.setNamedItem(att2);
			var att3 = doc.createAttribute("len");att3.text = connect_len[i];elt.attributes.setNamedItem(att3);
			rootNode.appendChild(elt);
		}
  };
	
	clusteringGraph.prototype.exist_count = function() {
		exist_count = 0;
		for(i=0;i<this.nodes.length;i++)
			if(this.nodes[i].exist)exist_count++;
		return exist_count;
  };
  return clusteringGraph;
})();

//実行用関数
function ClusteringInit(){
	Cnodes.length = 0;
	for(i=0;i<nodes.length;i++)
		Cnodes.push(new CNode(nodes[i].value,nodes[i].x,nodes[i].y));
	clustering = new clusteringGraph(Cnodes);
	clustering.calc();
}

//======================================================
//関数
//======================================================
//2点間の距離
function calcDistance(x1,y1,x2,y2) {
　　var a, b, d;
　　a = x1 - x2;
　　b = y1 - y2;
　　d = Math.sqrt(Math.pow(a,2) + Math.pow(b,2));
　　return d;
};

