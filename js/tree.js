var Canvas,Starting_point,TreeNode,Tree;
//======================================================
//キャンバスクラス
//======================================================
Canvas = (function() {
  function Canvas(el) {
    this.ctx = el.getContext('2d');
    this.width = el.width;
    this.height = el.height;
  }

  Canvas.prototype.add = function(graph) {
    this.graph = graph;
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
	
	Canvas.prototype.Rect = function(y1, y2, d, bg) {
    if (bg == null)bg = '#F5DEB3';
    return this.keep((function(_this) {
      return function() {
        _this.ctx.fillStyle = bg;
        _this.ctx.fillRect( 100, y1, d-1, y2-y1-1);
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
	var	r_height = 50;//デバック用
	var	r_width = 5;//デバック用
	this.drawaxis();
	
	//マウス内の結合部分を表示
	for (var i=0;i<this.graph.connection.length;i++) {
			var dist = this.graph.connection[i].dist;
			var a = this.graph.connection[i].a;
			var Node_a=this.graph.starting_point[this.NodeCount(a)];
			var b = this.graph.connection[i].b;
			var Node_b=this.graph.starting_point[this.NodeCount(b)];
		
			if(dist*r_width+100<mouseX)
			this.Rect(
				Node_a.heigth*r_height	+50,
				Node_b.heigth*r_height	+50,
				dist*r_width		
			);
			//console.log(Node_a.heigth*r_height+50,Node_b.heigth*r_height+50,dist*r_width);
			this.graph.starting_point[this.NodeCount(a)].change(Node_b.heigth,dist);
	}
	//最初に戻す
	for (var i=0;i<this.graph.starting_point.length;i++){
		this.graph.starting_point[i].return();
	}
  for (var i=0;i<this.graph.connection.length;i++) {
			var dist = this.graph.connection[i].dist;
			var a = this.graph.connection[i].a;
			var Node_a=this.graph.starting_point[this.NodeCount(a)];
			var b = this.graph.connection[i].b;
			var Node_b=this.graph.starting_point[this.NodeCount(b)];
		
      this.line(
				Node_a.width*r_width		+100,
				Node_a.heigth*r_height	+50,
				dist*r_width						+100,
				Node_a.heigth*r_height	+50,
				'#00bfff'
			);
		 	this.line(
				Node_b.width*r_width		+100,
				Node_b.heigth*r_height	+50,
				dist*r_width						+100,
				Node_b.heigth*r_height	+50,
				'#00bfff'
			);
			this.line(
				dist*r_width						+100,
				Node_a.heigth*r_height	+50,
				dist*r_width						+100,
				Node_b.heigth*r_height	+50,
				'#d2691e'
			);
			
			//マウスと接しているか
			this.NodeTouch(
				Node_a.width*r_width+100,
				dist*r_width+100,
				Node_a.heigth*r_height+50
			);
			this.NodeTouch(
				Node_b.width*r_width+100,
				dist*r_width+100,
				Node_b.heigth*r_height+50
			);
		
			if(Node_a.init==true)
				this.text( Node_a.value, 50, Node_a.heigth*r_height+50, '#F00', '18px bold');
			if(Node_b.init==true)
				this.text( Node_b.value, 50, Node_b.heigth*r_height+50, '#F00', '18px bold');
			//console.log(Node_a.width,dist,Node_a.heigth);
			//console.log(Node_b.width,dist,Node_b.heigth);
			//console.log("---------------------------");
			this.graph.starting_point[this.NodeCount(a)].change(Node_b.heigth,dist);
   }
		//console.log("******************************");
    return;
  };
	
	//始点の配列番号を返す。
	Canvas.prototype.NodeCount = function(value) {
		for (var i=0;i<this.graph.starting_point.length;i++)
			if(this.graph.starting_point[i].value==value)return i;
		return 0;
	}
	
	//軸の表示
	Canvas.prototype.drawaxis = function() {
		var wid = this.width;
    var hei = this.height;
		for(i=1;i<(wid/100);i++){
			this.line( 100*i, 0, 100*i, 20, '#CCC');
			this.text(100*i, 100*i, 20, '#999');
		}
		this.line( 0, 0, 0, hei, '#CCC');
		this.line( 0, hei, wid, hei, '#CCC');
		this.line( wid, 0, wid, hei, '#CCC');
		this.line( 0, 0, wid, 0, '#CCC');
		//赤線
		this.line( mouseX, 0, mouseX,　canvas.height, '#F00');
		this.text( mouseX, mouseX, 20, '#F00', '18px bold');
		return;		
  };
	
	//赤軸に触れている時に黄色
	Canvas.prototype.NodeTouch = function(a,b,y) {
		if(a<mouseX&&mouseX<b){
			this.circle(mouseX,y,5,'#FFD700');
		}
		return 0;
	}
	
  return Canvas;
})();

//======================================================
//ノードクラス
//======================================================
TreeNode = (function() {
  
  function TreeNode(dist,a,b) {
    this.id = Tree._id++;
    this.dist = dist;
    this.a = a;
    this.b = b;
  }
    return TreeNode;
})();

//======================================================
//各ノードの始点を記憶しておくクラス
//======================================================
Starting_point = (function() {
  
  function Starting_point(value) {
		this.value	= value;
    this.heigth	= Starting_point.count++;
    this.width	= 0;
		this.init_hei = this.heigth;
		this.init = true;//初期値かどうか
  }
	
	Starting_point.prototype.change = function(heigth,width){
		this.heigth	= (this.heigth+heigth)/2;
		this.width	= width;
		this.init = false;
	}
	
	Starting_point.prototype.return = function(){
		this.heigth	= this.init_hei;
		this.width	= 0;
		this.init 	= true;
	}
  return Starting_point;
})();

//======================================================
//接続クラス
//======================================================
Tree = (function() { 
  
  function Tree() {
		Starting_point.count = 0;
		TreeNode._id = 0;
    this.connection = [];
		this.starting_point = [];
  }
  
  Tree.prototype.connect = function(dist,a,b){
    this.connection.push(new TreeNode(dist,a,b));
		var exist_a=false;var exist_b=false;
		for(i=0;i<this.starting_point.length;i++){
			if(this.starting_point[i].value == a)exist_a=true;
			if(this.starting_point[i].value == b)exist_b=true;
		}
		if(!exist_a)this.starting_point.push(new Starting_point(parseInt(a)));
		if(!exist_b)this.starting_point.push(new Starting_point(parseInt(b)));
  }    
  return Tree;
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
function MouseDownFunc(e){}
function MouseUpFunc(e){move_flag = 0;}
function MouseInit(){
	move_flag = 0;
	setInterval(function() {}, 20);
	if(document.addEventListener){//イベントリスナーに対応している
		document.addEventListener("mousemove" , MouseMoveFunc);
		document.getElementById("canvas").addEventListener("mousedown",MouseDownFunc);//押下
		document.getElementById("canvas").addEventListener("mouseup",MouseUpFunc);//押上
	}else if(document.attachEvent){
		document.attachEvent("onmousemove" , MouseMoveFunc);
	}
}

