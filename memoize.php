<?php

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Password Memorizer</title>
    <script src="modernizr.js"></script>
    <script src="jsHash.js"></script>
    <script src="sjcl.js"></script>
    <script src="seedrandom.js"></script>

<style>
#everything {
    margin: 20px;
    max-width: 700px;
}
#canvasOne {
    margin-top: 20px;
    margin-bottom: 20px;
    border: solid black 5px;
    position: relative;
    /* must match the canvas size */
    width: 400px;
    height: 400px;
}
#txtPassword {
    border: solid black 1px;
    margin-bottom: 5px;
    width: 410px;
}
</style>

<script type="text/javascript">

window.addEventListener("load", eventWindowLoaded, false);

function eventWindowLoaded(e) {
    canvasApp();
}

function canvasSupport() {
    return Modernizr.canvas;
}

function canvasApp() {

    var MAX_KEY_LENGTH = 13;
    var theCanvas = document.getElementById("canvasOne");
    var context = theCanvas.getContext("2d");

    if (!canvasSupport()) {
        alert('Your browser does not support HTML5 Canvas. Please try one that does.');
        return;
    }

    var imagePaths = [ <?php
            $dir = new DirectoryIterator("password-images");
            foreach ($dir as $fileinfo) {
                if (!$fileinfo->isDot()) {
                    echo "\"password-images/{$fileinfo->getFilename()}\", ";
                }
            }
        ?> ];
    imagePaths.sort();

    var loaded_count = 0;
    var images = [];
    for (var i = 0; i < imagePaths.length; i++) {
        images[i] = new Image();
        images[i].addEventListener("load", imageLoaded, false);
        images[i].src = imagePaths[i];
    }

    drawLoading();

    function imageLoaded(e) {
        loaded_count += 1;
        drawLoading();
        if (loaded_count == imagePaths.length) {
            btnRandomPassword_click();
        }
    }

    function drawLoading() {
        context.fillStyle = "#000000";
        context.fillRect(0, 0, theCanvas.width, theCanvas.height);
        context.fillStyle = "#FFFFFF";
        context.font = "bold 20px monospace";
        context.fillText("Loading images...", 20, 30);

        context.fillStyle = "#00FF00";
        context.fillRect(50, 50, 300 * loaded_count / imagePaths.length, 20);
        context.strokeStyle = "#FFFFFF";
        context.lineWidth = 2;
        context.strokeRect(50, 50, 300, 20);

        context.fillStyle = "#FFFFFF";
        context.font = "normal 12px monospace";
        context.fillText(loaded_count + "/" + imagePaths.length, 180, 90);
    }


    var index = 0;
    var image_groups = [];
    for (var i = 0; i < MAX_KEY_LENGTH; i++) {
        image_groups[i] = [];
        for (var j = 0; j < 16; j++) {
            image_groups[i].push(images[index++]);
        }
    }

    var previous_mode = 'stopped';
    var mode = 'stopped';
    var mouse_x = -1;
    var mouse_y = -1;
    var highlight_x = -1;
    var highlight_y = -1;
    var key_train = [];
    var key_input = [];

    theCanvas.addEventListener("mouseup", canvas_mouseup, false);
    function canvas_mouseup(e) {
        if (mode !== 'stopped') {
            var coords = getMouse(e, theCanvas);
            // var canvas_x = e.clientX - theCanvas.offsetLeft;
            // var canvas_y = e.clientY - theCanvas.offsetTop;
            var canvas_x = coords.x;
            var canvas_y = coords.y;

            if (
                (canvas_x >= 0 && canvas_x < theCanvas.width) &&
                (canvas_y >= 0 && canvas_y < theCanvas.height)
            ) {
                mouse_x = Math.floor(canvas_x / 100);
                mouse_y = Math.floor(canvas_y / 100);
                key_input.push(mouse_y * 4 + mouse_x);
            } else { 
                mouse_x = -1;
                mouse_y = -1;
            }
            drawScreen();
        }
    }

    var txtPassword = document.getElementById("txtPassword");

    var btnRandomPassword = document.getElementById("btnRandomPassword");
    btnRandomPassword.addEventListener("click", btnRandomPassword_click, false);
    function btnRandomPassword_click(e) {
        previous_mode = 'training';
        mode = 'training';
        key_train = randomKey();
        key_input = [];
        txtPassword.value = encodeKey(key_train);
        drawScreen();
    }

    var btnLoad = document.getElementById("btnLoad");
    btnLoad.addEventListener("click", btnLoad_click, false);
    function btnLoad_click(e) {
        previous_mode = 'loading';
        mode = 'loading';
        key_input = [];
        drawScreen();
    }

    var btnBack = document.getElementById("btnBack");
    btnBack.addEventListener("click", btnBack_click, false);
    function btnBack_click(e) {
        key_input.pop();
        drawScreen();
    }

    var btnRestart = document.getElementById("btnRestart");
    btnRestart.addEventListener("click", btnRestart_click, false);
    function btnRestart_click(e) {
        mode = previous_mode;
        key_input = [];
        drawScreen();
    }

    function drawScreen() {

        if (mode === 'training') {
            if (key_input.length > 0 && key_input[key_input.length - 1] !== key_train[key_input.length - 1]) {
                context.fillStyle = "#FF0000";
                context.fillRect(0, 0, theCanvas.width, theCanvas.height);
                context.fillStyle = "#000000";
                context.font = "bold 16px monospace";
                context.fillText("You clicked the wrong one. Go back.", 20, 50);
                return;
            }
            // TODO: make sure they click the right one
            if (key_input.length == key_train.length) {
                mode = 'stopped';
            } else {
                next_nibble = key_train[key_input.length];
                highlight_x = next_nibble % 4;
                highlight_y = Math.floor(next_nibble / 4);
            }
        } else if (mode === 'loading') {
            txtPassword.value = encodeKey(key_input);
            highlight_x = -1;
            highlight_y = -1;
            if (key_input.length == MAX_KEY_LENGTH) {
                mode = 'stopped';
            }
        }

        if (mode === 'stopped') {
            context.fillStyle = "#000000";
            context.fillRect(0, 0, theCanvas.width, theCanvas.height);
            context.fillStyle = "#FFFFFF";
            context.font = "bold 16px monospace";
            context.fillText("End.", 20, 50);
            return;
        }

        // TODO: random image order, and random category order
        // var random = getCSPRNG(key_train.toString());
        var image_group = image_groups[key_input.length];

        for (var i = 0; i < 16; i++) {
            var img_x = (i % 4) * 100;
            var img_y = Math.floor(i / 4) * 100;
            context.drawImage(image_group[i], img_x, img_y, 100, 100);
        }

        if (highlight_x > -1 && highlight_y > -1) {
            context.strokeStyle = "#00FF00";
            context.lineWidth = 4;
            context.strokeRect(highlight_x * 100 + 2, highlight_y * 100 + 2, 100 - 4, 100 - 4);
        }

        // if (mouse_x > -1 && mouse_y > -1) {
        //     context.strokeStyle = "#0000FF";
        //     context.lineWidth = 3;
        //     context.strokeRect(mouse_x * 100, mouse_y * 100, 100, 100);
        // }
    }

    function randomKey() {
        var words = sjcl.random.randomWords(MAX_KEY_LENGTH, 0);
        var key = [];
        // FIXME: check that this is proper
        for (var i = 0; i < MAX_KEY_LENGTH; i++) {
            key.push(words[i] & 0xF);
        }
        return key;
    }

    function encodeKey(key) {
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
    }

    function getCSPRNG(seed) {
        // TODO: implement properly with AES-CTR or something
        var state = seed;
        return function() {
            state = sha256(state);
            Math.seedrandom(state);
            var rand = Math.random();
            Math.seedrandom(new Date().getTime());
            return rand;
        }
    }

    function sha256(text) {
        return jsHash.sha2.str_sha256(text);
    }

    // FIXME HACK: Do this the right way with jquery -- this is from
    // http://stackoverflow.com/a/10450761
    stylePaddingLeft = parseInt(document.defaultView.getComputedStyle(theCanvas, null)['paddingLeft'], 10)      || 0;
    stylePaddingTop  = parseInt(document.defaultView.getComputedStyle(theCanvas, null)['paddingTop'], 10)       || 0;
    styleBorderLeft  = parseInt(document.defaultView.getComputedStyle(theCanvas, null)['borderLeftWidth'], 10)  || 0;
    styleBorderTop   = parseInt(document.defaultView.getComputedStyle(theCanvas, null)['borderTopWidth'], 10)   || 0;
  // Some pages have fixed-position bars (like the stumbleupon bar) at the top or left of the page
  // They will mess up mouse coordinates and this fixes that
    var html = document.body.parentNode;
    htmlTop = html.offsetTop;
    htmlLeft = html.offsetLeft;
    // Creates an object with x and y defined,
    // set to the mouse position relative to the state's canvas
    // If you wanna be super-correct this can be tricky,
    // we have to worry about padding and borders
    // takes an event and a reference to the canvas
    function getMouse(e, canvas) {
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
    offsetX += stylePaddingLeft + styleBorderLeft + htmlLeft;
    offsetY += stylePaddingTop + styleBorderTop + htmlTop;

    mx = e.pageX - offsetX;
    my = e.pageY - offsetY;

    // We return a simple javascript object with x and y defined
    return {x: mx, y: my};
    }

}

</script>
</head>
<body>

    <div id="everything">
        <p style="font-size: 16pt;">
            <strong>Image Sequence Keys</strong>
        </p>

        <p>
            This is a key input system based on the principal that recognition
            is easier than recall.
        </p>

        <p>
            <strong>Instructions:</strong>
        </p>


        <ol>
            <li>
                Click &quot;Random Key&quot; to have a random password generated for you.
            </li>
            <li>
                Click the green squares while memorizing the images inside them.
            </li>
            <li>
                If you need more practice, click &quot;Restart&quot; and repeat the process.
            </li>
            <li>
                Once you have memorized the sequence of images, you can re-create the key at a later
                time by clicking &quot;Input Key&quot; then clicking the same sequence of
                images.
            </li>
        </ol>

        <p>
            <strong>Warning:</strong>
        </p>

        <p>
            This page is being actively developped and is being released as
            a demonstration only. Do not rely on it to consistently reproduce
            the same password until it is finished.
        </p>

        <form>
            <input type="text" id="txtPassword" readonly /> <br />
            <input type="button" id="btnRandomPassword" value="Random Key"/>
            <input type="button" id="btnLoad" value="Input Key"/>
        </form>

        <canvas id="canvasOne" width="400" height="400">
            Your browser does not support HTML5 Canvas.
        </canvas>

        <form>
            <input type="button" id="btnBack" value="Go Back One Step" />
            <input type="button" id="btnRestart" value="Restart" />
        </form>

        <p>
        Written by <a href="https://defuse.ca/">Taylor Hornby</a>.
        Source code on <a href="https://github.com/defuse/image-passwords">GitHub</a>.
        </p>

        <p>
            <strong>Prior Work</strong>
        </p>

        <ul>
            <li><a
            href="http://csrc.nist.gov/publications/nistir/nistir-7030.pdf">http://csrc.nist.gov/publications/nistir/nistir-7030.pdf</a></li>
            <li><a
            href="http://www.google.com/patents/US20040230843">http://www.google.com/patents/US20040230843</a></li>
        </ul>
    </div>
</body>
</html>
