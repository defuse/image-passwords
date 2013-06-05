var imgpass;
if (!imgpass) {
    imgpass = {};
}

imgpass.MAX_KEY_LENGTH = 20;
imgpass.CELL_WIDTH = 120;
imgpass.CELL_HEIGHT = 120;
imgpass.NUM_COLUMNS = 4;
imgpass.NUM_ROWS = 4;
imgpass.previous_mode = 'stopped';
imgpass.mode = 'stopped';
imgpass.mouse_x = -1;
imgpass.mouse_y = -1;
imgpass.highlight_x = -1;
imgpass.highlight_y = -1;
imgpass.key_train = [];
imgpass.key_input = [];


imgpass.canvasSupport = function () {
    return Modernizr.canvas;
};

imgpass.sha256 = function (text) {
    return jsHash.sha2.str_sha256(text);
};

imgpass.randomKey = function () {
    var words = sjcl.random.randomWords(imgpass.MAX_KEY_LENGTH, 0);
    var key = [];
    for (var i = 0; i < imgpass.MAX_KEY_LENGTH; i++) {
        key.push(words[i] & 0xF);
    }
    return key;
};

imgpass.encodeKey = function (key) {
    var hex = [];
    for (var i = 0; i < key.length; i++) {
        if (0 <= key[i] && key[i] <= 9) {
            hex.push(String.fromCharCode(48 + key[i]));
        } else if (10 <= key[i] && key[i] <= 15) {
            hex.push(String.fromCharCode(65 + key[i] - 10));
        } else {
            throw 'Invalid key nibble.';
        }
    }
    return hex.join('');
};

imgpass.getCSPRNG = function (seed) {
    // TODO: implement properly with AES-CTR or something
    var state = seed;
    return function() {
        state = imgpass.sha256(state);
        Math.seedrandom(state);
        var rand = Math.random();
        Math.seedrandom(new Date().getTime());
        return rand;
    }
};

imgpass.getMouse = function (e, canvas) {
    var element = canvas, offsetX = 0, offsetY = 0, mx, my;

    // Compute the total offset. It's possible to cache this if you want
    if (element.offsetParent !== undefined) {
        do {
        offsetX += element.offsetLeft;
        offsetY += element.offsetTop;
        } while ((element = element.offsetParent));
    }

    // Add padding and border style widths to offset
    // Also add the <html> offsets in case there's a position:fixed bar (like the stumbleupon bar)
    // This part is not strictly necessary, it depends on your styling
    offsetX += imgpass.stylePaddingLeft + imgpass.styleBorderLeft + imgpass.htmlLeft;
    offsetY += imgpass.stylePaddingTop + imgpass.styleBorderTop + imgpass.htmlTop;

    mx = e.pageX - offsetX;
    my = e.pageY - offsetY;

    // We return a simple javascript object with x and y defined
    return {x: mx, y: my};
};

imgpass.drawScreen = function () {
    if (imgpass.mode === 'training') {
        if (imgpass.key_input.length > 0 && imgpass.key_input[imgpass.key_input.length - 1] !== imgpass.key_train[imgpass.key_input.length - 1]) {
            imgpass.context.fillStyle = "#FF0000";
            imgpass.context.fillRect(0, 0, imgpass.theCanvas.width, imgpass.theCanvas.height);
            imgpass.context.fillStyle = "#000000";
            imgpass.context.font = "bold 16px monospace";
            imgpass.context.fillText("You clicked the wrong one. Go back.", 20, 50);
            return;
        }
        if (imgpass.key_input.length == imgpass.key_train.length) {
            imgpass.mode = 'stopped';
        } else {
            next_nibble = imgpass.key_train[imgpass.key_input.length];
            imgpass.highlight_x = next_nibble % 4;
            imgpass.highlight_y = Math.floor(next_nibble / 4);
        }
    } else if (imgpass.mode === 'loading') {
        imgpass.txtPassword.value = imgpass.encodeKey(imgpass.key_input);
        imgpass.highlight_x = -1;
        imgpass.highlight_y = -1;
        if (imgpass.key_input.length == imgpass.MAX_KEY_LENGTH) {
            imgpass.mode = 'stopped';
        }
    }

    if (imgpass.mode === 'stopped') {
        imgpass.context.fillStyle = "#000000";
        imgpass.context.fillRect(0, 0, imgpass.theCanvas.width, imgpass.theCanvas.height);
        imgpass.context.fillStyle = "#FFFFFF";
        imgpass.context.font = "bold 16px monospace";
        imgpass.context.fillText("End.", 20, 50);
        return;
    }

    var image_group = imgpass.image_groups[imgpass.key_input.length];

    for (var i = 0; i < 16; i++) {
        var img_x = (i % 4) * imgpass.CELL_WIDTH;
        var img_y = Math.floor(i / 4) * imgpass.CELL_HEIGHT;
        imgpass.context.drawImage(image_group[i], img_x, img_y, imgpass.CELL_WIDTH, imgpass.CELL_HEIGHT);
    }

    if (imgpass.highlight_x > -1 && imgpass.highlight_y > -1) {
        imgpass.context.strokeStyle = "#00FF00";
        imgpass.context.lineWidth = 4;
        imgpass.context.strokeRect(imgpass.highlight_x * imgpass.CELL_WIDTH + 2, imgpass.highlight_y * imgpass.CELL_HEIGHT + 2, imgpass.CELL_WIDTH - 4, imgpass.CELL_HEIGHT - 4);
    }
};

imgpass.canvasApp = function (imagePaths) {
    sjcl.random.startCollectors();
    imgpass.theCanvas = document.getElementById("canvasOne");
    imgpass.context = imgpass.theCanvas.getContext("2d");
    imgpass.txtPassword = document.getElementById("txtPassword");
    // FIXME HACK: Do this the right way with jquery -- this is from
    // http://stackoverflow.com/a/10450761
    imgpass.stylePaddingLeft = parseInt(document.defaultView.getComputedStyle(imgpass.theCanvas, null)['paddingLeft'], 10)      || 0;
    imgpass.stylePaddingTop  = parseInt(document.defaultView.getComputedStyle(imgpass.theCanvas, null)['paddingTop'], 10)       || 0;
    imgpass.styleBorderLeft  = parseInt(document.defaultView.getComputedStyle(imgpass.theCanvas, null)['borderLeftWidth'], 10)  || 0;
    imgpass.styleBorderTop   = parseInt(document.defaultView.getComputedStyle(imgpass.theCanvas, null)['borderTopWidth'], 10)   || 0;
    var html = document.body.parentNode;
    imgpass.htmlTop = html.offsetTop;
    imgpass.htmlLeft = html.offsetLeft;

    // Make sure the ordering is consistent.
    imagePaths.sort();

    if (!imgpass.canvasSupport()) {
        alert('Your browser does not support HTML5 Canvas. Please try one that does.');
        return;
    }

    var loaded_count = 0;
    var images = [];
    var index = 0;

    drawLoading();

    for (var i = 0; i < imagePaths.length; i++) {
        images[i] = new Image();
        images[i].addEventListener("load", imageLoaded, false);
        images[i].src = imagePaths[i];
    }

    function imageLoaded(e) {
        loaded_count += 1;
        drawLoading();
        if (loaded_count == imagePaths.length) {
            imgpass.image_groups = [];
            for (var i = 0; i < imgpass.MAX_KEY_LENGTH; i++) {
                imgpass.image_groups[i] = [];
                for (var j = 0; j < imgpass.NUM_ROWS * imgpass.NUM_COLUMNS; j++) {
                    imgpass.image_groups[i].push(images[index++]);
                }
            }
            btnRandomPassword_click();
        }
    }

    function drawLoading() {
        imgpass.context.fillStyle = "#000000";
        imgpass.context.fillRect(0, 0, imgpass.theCanvas.width, imgpass.theCanvas.height);
        imgpass.context.fillStyle = "#FFFFFF";
        imgpass.context.font = "bold 20px monospace";
        imgpass.context.fillText("Loading images...", 20, 30);

        imgpass.context.fillStyle = "#00FF00";
        imgpass.context.fillRect(50, 50, 300 * loaded_count / imagePaths.length, 20);
        imgpass.context.strokeStyle = "#FFFFFF";
        imgpass.context.lineWidth = 2;
        imgpass.context.strokeRect(50, 50, 300, 20);

        imgpass.context.fillStyle = "#FFFFFF";
        imgpass.context.font = "normal 12px monospace";
        imgpass.context.fillText(loaded_count + "/" + imagePaths.length, 180, 90);
    }


    var btnRandomPassword = document.getElementById("btnRandomPassword");
    btnRandomPassword.addEventListener("click", btnRandomPassword_click, false);
    function btnRandomPassword_click(e) {
        imgpass.previous_mode = 'training';
        imgpass.mode = 'training';
        imgpass.key_train = imgpass.randomKey();
        imgpass.key_input = [];
        imgpass.txtPassword.value = imgpass.encodeKey(imgpass.key_train);
        imgpass.drawScreen();
    }

    var btnLoad = document.getElementById("btnLoad");
    btnLoad.addEventListener("click", btnLoad_click, false);
    function btnLoad_click(e) {
        imgpass.previous_mode = 'loading';
        imgpass.mode = 'loading';
        imgpass.key_input = [];
        imgpass.drawScreen();
    }

    var btnBack = document.getElementById("btnBack");
    btnBack.addEventListener("click", btnBack_click, false);
    function btnBack_click(e) {
        imgpass.key_input.pop();
        imgpass.drawScreen();
    }

    var btnRestart = document.getElementById("btnRestart");
    btnRestart.addEventListener("click", btnRestart_click, false);
    function btnRestart_click(e) {
        imgpass.mode = imgpass.previous_mode;
        imgpass.key_input = [];
        imgpass.drawScreen();
    }

    imgpass.theCanvas.addEventListener("mouseup", canvas_mouseup, false);
    function canvas_mouseup(e) {
        if (imgpass.mode !== 'stopped') {
            var coords = imgpass.getMouse(e, imgpass.theCanvas);
            var canvas_x = coords.x;
            var canvas_y = coords.y;

            if (
                (canvas_x >= 0 && canvas_x < imgpass.theCanvas.width) &&
                (canvas_y >= 0 && canvas_y < imgpass.theCanvas.height)
            ) {
                imgpass.mouse_x = Math.floor(canvas_x / imgpass.CELL_WIDTH);
                imgpass.mouse_y = Math.floor(canvas_y / imgpass.CELL_HEIGHT);
                imgpass.key_input.push(imgpass.mouse_y * 4 + imgpass.mouse_x);
            } else { 
                imgpass.mouse_x = -1;
                imgpass.mouse_y = -1;
            }
            imgpass.drawScreen();
        }
}

};

