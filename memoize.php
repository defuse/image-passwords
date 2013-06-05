<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Password Memorizer</title>
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
        width: 480px;
        height: 480px;
    }
    #txtPassword {
        border: solid black 1px;
        margin-bottom: 5px;
        width: 490px;
    }
    </style>
    <script src="modernizr.js"></script>
    <script src="jsHash.js"></script>
    <script src="sjcl.js"></script>
    <script src="seedrandom.js"></script>
    <script src="imgpass.js"></script>
    <script type="text/javascript">
        window.addEventListener("load", eventWindowLoaded, false);

        function eventWindowLoaded(e) {
            var imagePaths = [ <?php
                    $dir = new DirectoryIterator("password-images");
                    foreach ($dir as $fileinfo) {
                        if (!$fileinfo->isDot()) {
                            echo "\"password-images/{$fileinfo->getFilename()}\", ";
                        }
                    }
                ?> ];
            imgpass.canvasApp(imagePaths);
        }
    </script>
</head>
<body>
    <div id="everything">
        <p style="font-size: 16pt;"> <strong>Image Sequence Keys</strong> </p>
        <p>
            This is a key input system based on the principal that recognition
            is easier than recall.
        </p>

        <p> <strong>Instructions:</strong> </p>
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

        <p> <strong>Warning:</strong> </p>
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

        <canvas id="canvasOne" width="480" height="480">
            Your browser does not support HTML5 Canvas.
        </canvas>

        <form>
            <input type="button" id="btnBack" value="Go Back One Step" />
            <input type="button" id="btnRestart" value="Restart" />
        </form>

        <p>
            Written by <a href="https://defuse.ca/">Taylor Hornby</a>.
            Source code on 
            <a href="https://github.com/defuse/image-passwords">GitHub</a>.
        </p>

        <p>
            <strong>Prior Work</strong>
        </p>

        <ul>
            <li>
                <a href="http://csrc.nist.gov/publications/nistir/nistir-7030.pdf">
                    http://csrc.nist.gov/publications/nistir/nistir-7030.pdf
                </a>
            </li>
            <li>
                <a href="http://www.google.com/patents/US20040230843">
                    http://www.google.com/patents/US20040230843
                </a>
            </li>
        </ul>
    </div>
</body>
</html>
