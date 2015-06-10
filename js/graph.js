var Canvas, ForceDirectedGraph, Node;

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
    if (bg == null) {
      bg = '#000';
    }
    this.keep((function(_this) {
      return function() {
        _this.ctx.fillStyle = bg;
        return _this.ctx.fillRect(0, 0, _this.width, _this.height);
      };
    })(this));
    return this;
  };

  Canvas.prototype.circle = function(x, y, r, bg) {
    if (bg == null) {
      bg = '#000';
    }
    return this.keep((function(_this) {
      return function() {
        _this.ctx.fillStyle = bg;
        _this.ctx.arc(x, y, r, 0, 2 * Math.PI);
        return _this.ctx.fill();
      };
    })(this));
  };

  Canvas.prototype.line = function(fromX, fromY, toX, toY, color, width) {
    if (color == null) {
      color = '#999';
    }
    if (width == null) {
      width = 2;
    }
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
    if (color == null) {
      color = 'white';
    }
    if (font == null) {
      font = '14px bold';
    }
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
    var el, k, len, ref, results;
    ref = this.elements;
    results = [];
    for (k = 0, len = ref.length; k < len; k++) {
      el = ref[k];
      if (el.constructor === ForceDirectedGraph) {
        results.push(this._drawGraph(el));
      } else {
        results.push(void 0);
      }
    }
    return results;
  };

  Canvas.prototype._drawGraph = function(graph) {
    var connected, i, j, k, l, len, len1, len2, m, n, node, o, ref, ref1, ref2, ref3, results;
    connected = [];
    for (i = k = 0, ref = graph.nodes.length; 0 <= ref ? k < ref : k > ref; i = 0 <= ref ? ++k : --k) {
      connected[i] = [];
    }
    ref1 = graph.nodes;
    for (i = l = 0, len = ref1.length; l < len; i = ++l) {
      node = ref1[i];
      ref2 = node.connections;
      for (j = m = 0, len1 = ref2.length; m < len1; j = ++m) {
        n = ref2[j];
        if (connected[i][j] || connected[j][i]) {
          continue;
        }
        connected[i][j] = true;
        connected[j][i] = true;
        this.line(node.x, node.y, n.x, n.y);
      }
    }
    ref3 = graph.nodes;
    results = [];
    for (o = 0, len2 = ref3.length; o < len2; o++) {
      node = ref3[o];
      if (node.isFocus) {
        this.circle(node.x, node.y, node.r + 5, '#444');
      }
      this.circle(node.x, node.y, node.r, node.background);
      results.push(this.text(node.value, node.x, node.y));
    }
    return results;
  };

  return Canvas;

})();

Node = (function() {
  var colorPalette;

  Node._lastID = 0;

  colorPalette = ['#556270', '#4ECDC4', '#C7F464', '#FF6B6B', '#C44D58', '#FE5F55', '#777DA7', '#94C9A9', '#885053', '#DDFC74', '#D3F9B5', '#D9B8C4'];

  function Node(value, x1, y1, background, r1) {
    var idx;
    this.value = value;
    this.x = x1;
    this.y = y1;
    this.background = background;
    this.r = r1 != null ? r1 : 20;
    this.id = Node._lastID++;
    this.connections = [];
    this.vx = 0;
    this.vy = 0;
    idx = ~~(Math.random() * colorPalette.length);
    if (!this.background) {
      this.background = colorPalette[idx];
    }
  }

  Node.prototype.connect = function(node) {
    return this.connections.push(node);
  };

  Node.prototype.focus = function() {
    return this.isFocus = true;
  };

  Node.prototype.blur = function() {
    return this.isFocus = false;
  };

  return Node;

})();

ForceDirectedGraph = (function() {
  ForceDirectedGraph.BOUNCE = 0.02;//バネ定数(BOUNCE < 0.1[推奨])

  ForceDirectedGraph.ATTENUATION = 0.7;//減衰定数(ATTENUATION < 1[必須])

  ForceDirectedGraph.COULOMB = 200;//クーロン数

  function ForceDirectedGraph(nodes1) {
    this.nodes = nodes1;
  }

  ForceDirectedGraph.prototype.add = function(node) {
    return this.nodes.push(node);
  };

  ForceDirectedGraph.prototype.connect = function(a, b) {
		this.nodes[a].r--;
		this.nodes[b].r++;
    this.nodes[a].connect(this.nodes[b]);
    return this.nodes[b].connect(this.nodes[a]);
  };

  ForceDirectedGraph.prototype.balance = function(max) {
    var distX, distY, fx, fy, k, l, len, len1, len2, m, n, number, ref, ref1, ref2, results, rsq, targetNode;
    ref = this.nodes;
    results = [];
    for (number = k = 0, len = ref.length; k < len; number = ++k) {
      targetNode = ref[number];
      if (targetNode.isFocus) {
        continue;
      }
      fx = 0;
      fy = 0;
      ref1 = this.nodes;
      for (l = 0, len1 = ref1.length; l < len1; l++) {
        n = ref1[l];
        if (targetNode === n) {
          continue;
        }
        distX = targetNode.x - n.x;
        distY = targetNode.y - n.y;
        rsq = distX * distX + distY * distY;
        fx += ForceDirectedGraph.COULOMB * distX / rsq;
        fy += ForceDirectedGraph.COULOMB * distY / rsq;
      }
      ref2 = targetNode.connections;
      for (m = 0, len2 = ref2.length; m < len2; m++) {
        n = ref2[m];
        distX = n.x - targetNode.x;
        distY = n.y - targetNode.y;
        fx += ForceDirectedGraph.BOUNCE * distX;
        fy += ForceDirectedGraph.BOUNCE * distY;
      }
      targetNode.vx = (targetNode.vx + fx) * ForceDirectedGraph.ATTENUATION;
      targetNode.vy = (targetNode.vy + fy) * ForceDirectedGraph.ATTENUATION;
      if (number !== max) {
        targetNode.x += targetNode.vx;
        results.push(targetNode.y += targetNode.vy);
      } else {
        results.push(void 0);
      }
    }
    return results;
  };

  return ForceDirectedGraph;

})();