<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ __('Localization Practice') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous">
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Teko:wght@300&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: rgb(43, 43, 43);
            overflow-y: hidden;
            font-family: 'Teko', sans-serif;
        }

        canvas {
            display: block;
            position: relative;
            margin: auto;
            -webkit-mask-image: -webkit-gradient(linear, left 0%, left bottom, from(rgba(0, 0, 0, 0)), to(rgba(0, 0, 0, 1)))
        }


        .smoke {
            position: relative;
            margin: auto;
        }

        .overlay {
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            right: 0;
            text-align: center;
            line-height: 80vh;
            font-family: sans-serif;
            font-weight: bold;
            font-size: 3em;
            color: #b2b2b2;
        }
    </style>

</head>

<body class="antialiased">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">{{ __('Joton') }}</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false"
                aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="#">{{ __('Home') }}</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            @php
                                $langArray = Config::get('app.available_locales');
                                $key = 'en';
                                if (Session::has('locale')) {
                                    $key = Session::get('locale');
                                }
                                $lang = array_search($key, $langArray);
                            @endphp
                            {{ $lang }}
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                            @foreach (Config::get('app.available_locales') as $locale_name => $available_locale)
                                @if ($available_locale === $lang)
                                    <li><a class="dropdown-item">{{ $locale_name }}</a></li>
                                @else
                                    <li><a class="dropdown-item"
                                            href="{{ route('lang', $available_locale) }}">{{ $locale_name }}</a>
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                    </li>
                </ul>
                <form class="d-flex">
                    <input class="form-control me-2" type="search" placeholder="{{ __('Search') }}"
                        aria-label="Search">
                    <button class="btn btn-outline-success" type="submit">{{ __('Search') }}</button>
                </form>
            </div>
        </div>
    </nav>
    <div class="container py-5">
        <div class="smoke">
            <canvas width="256px" height="1080px" id="experiment"></canvas>
            <div class="overlay">{{ __('Welcome to our website!') }}</div>
        </div>
    </div>
    <script>
        const canvas = document.querySelector('#experiment');
        const context = canvas.getContext('2d');

        const CANVAS_WIDTH = 800;
        const CANVAS_HEIGHT = 800;

        const CURVES = 3;
        const STEPS = 35;

        const SPEED = 0.4;

        function start() {
            context.clearRect(0, 0, 9999, 9999);

            const pathController = new PathController(3);
            pathController.ensureCanvasFilled();
            pathController.interpolatePaths();
            pathController.drawPaths();
            pathController.animate();
        }


        function PathController(count) {

            const MIN_LENGTH = CANVAS_HEIGHT + 1024;

            this.paths = [...Array(count)].map(() => new Path());

            this.extendPaths = () => {
                this.paths.forEach(p => p.extend())
            }

            this.ensureCanvasFilled = () => {
                this.paths.forEach(path => {
                    while (path.getBottom() < MIN_LENGTH) {
                        this.extendPaths();
                    }
                });

                this.paths.forEach(path => {
                    if (path.curves[0].endPoint < 0 - 512) {
                        this.shiftPaths();
                    }
                })

                this.interpolatePaths();
            }

            this.shiftPaths = () => {
                this.paths.forEach(path => path.shiftPath())
            }

            this.drawPaths = () => {
                this.paths.forEach(p => p.draw());
            }

            this.interpolatePaths = () => {
                this.paths.forEach((path, idx) => {
                    if (typeof this.paths[idx + 1] !== 'undefined') {
                        path.interpolateWith(this.paths[idx + 1]);
                    }
                });
            }

            this.stepPaths = () => {
                this.paths.forEach(p => p.step(SPEED));
            }

            this.clearCanvas = () => {
                context.clearRect(0, 0, 999, 999);
            }

            this.animate = () => {
                this.clearCanvas()
                this.stepPaths();
                this.ensureCanvasFilled();
                this.drawPaths();
                window.requestAnimationFrame(this.animate.bind(this));
            }
        }

        function Path() {
            this.curves = [];
            this.interpolatedCurves = [];

            // how much length to maintain
            this.minLength = CANVAS_HEIGHT + 512;

            this.extend = () => {
                let newCurve;
                if (this.curves.length) {
                    const parentCurve = this.curves[this.curves.length - 1];
                    childCurve = new Curve({
                        startPoint: [...parentCurve.endPoint]
                    });
                } else {
                    childCurve = new Curve();
                }
                this.curves.push(childCurve);
                return childCurve;
            }

            this.draw = () => {
                this.curves.forEach(c => {
                    c.draw('dimgray');
                });
                this.interpolatedCurves.forEach(c => {
                    c.forEach(d => d.draw('dimgray'));
                });
            }

            this.step = () => {
                this.curves.forEach(c => c.step());
                this.interpolatedCurves.forEach(c => c.forEach(d => d.step()));
            }

            this.interpolateWith = path => {
                this.interpolatedPair = path;
                this.interpolatedCurves = this.curves.map((curve, idx) => {
                    // for when some paths have more curves, just add
                    // more curves to this path
                    if (typeof path.curves[idx] == 'undefined') path.extend();
                    return curve.interpolateWith(path.curves[idx]);
                })
            }

            this.getLength = () => this.curves.reduce((acc, cur) => acc + cur.length, 0);

            this.getBottom = () => {
                if (!this.curves.length) return 0;
                return this.curves[this.curves.length - 1].endPoint[1];
            }


            this.shiftPath = () => {
                this.curves.shift();
            }

        }



        function Curve(params = {}) {

            const config = Object.assign({}, params);

            const MAX_WIDTH = 256;
            const MIN_WIDTH = 128;
            const MAX_LENGTH = 512;
            const MIN_LENGTH = 256;

            this.startPoint = config.startPoint || [randomInRange(0, MAX_WIDTH), 0];
            this.length = randomInRange(MIN_LENGTH, MAX_LENGTH);
            this.startWhiskerLength = config.startWhiskerLength || 128;
            this.endWhiskerLength = 128;

            // require the curve to start and end on opposite sides
            // with a margin of at least MIN_WIDTH
            let startX = this.startPoint[0];
            let endX;
            if (startX > MAX_WIDTH / 2) {
                endX = randomInRange(0, MIN_WIDTH);
            } else {
                endX = randomInRange(MIN_WIDTH, MAX_WIDTH);
            }
            this.endPoint = [endX, this.startPoint[1] + this.length];


            this.interpolatedCurves = [];
            this.attachedCurves = [];

            this.forcedBezier = config.forcedBezier;

            this.draw = (color = 'red') => {
                const bez = this.toBezier();
                context.beginPath();
                context.moveTo(...bez.startPoint);
                context.bezierCurveTo(...bez.cp1, ...bez.cp2, ...bez.endPoint);
                context.strokeStyle = color;
                context.lineWidth = 0.300;
                context.stroke();

                // this.drawPoints();
                // this.drawWhiskers();
            }

            this.step = () => {
                const oldBez = this.toBezier();
                const newBez = Object.assign({}, oldBez);
                newBez.startPoint[1] = oldBez.startPoint[1] - SPEED;
                newBez.endPoint[1] = oldBez.endPoint[1] - SPEED;
                newBez.cp1[1] = oldBez.cp1[1] - SPEED;
                newBez.cp2[1] = oldBez.cp2[1] - SPEED;
                this.forcedBezier = newBez;
            }

            this.toBezier = () => {
                return this.forcedBezier || {
                    startPoint: this.startPoint,
                    endPoint: this.endPoint,
                    cp1: [this.startPoint[0], this.startPoint[1] + this.startWhiskerLength],
                    cp2: [this.endPoint[0], this.endPoint[1] - this.endWhiskerLength]
                }
            }

            this.createAttachedCurve = () => {
                return new Curve(Object.assign({}, {
                    startPoint: [...this.endPoint],
                }));
            }

            this.createAttachedCurves = (count = 20) => {
                this.attachedCurve = this.createAttachedCurve();
                if (count - 1) this.attachedCurve.createAttachedCurves(count - 1);
                return [...this.attachedCurves];
            }

            this.drawPoints = () => {
                drawCircle(...this.toBezier().startPoint, 'blue');
                drawCircle(...this.toBezier().endPoint, 'blue');
            }

            this.drawWhiskers = () => {
                context.beginPath();
                context.moveTo(...this.toBezier().startPoint);
                context.lineTo(...this.toBezier().cp1);
                context.moveTo(...this.toBezier().endPoint);
                context.lineTo(...this.toBezier().cp2);
                context.strokeStyle = 'orange';
                context.stroke();


                drawCircle(...this.toBezier().cp1, 'orange');
                drawCircle(...this.toBezier().cp2, 'orange');
            }

            // yeet
            this.interpolateWith = curve => this.interpolatedCurves = interpolateCurves(this, curve);
        }

        function drawCircle(x, y, color) {
            const radius = 4;
            context.beginPath();
            context.arc(x, y, radius, 0, 2 * Math.PI, false);
            context.fillStyle = color;
            context.fill();
        }

        // returns new curves
        function interpolateCurves(curveA, curveB, count = STEPS) {
            const result = [];
            const bezierA = curveA.toBezier();
            const bezierB = curveB.toBezier();

            // draw one curve for each desired step
            for (let i = 1; i <= count; i++) {
                const progress = i / (count + 1);
                const curve = new Curve();
                const params = ['startPoint', 'endPoint', 'cp1', 'cp2'];
                const forcedBezier = {
                    startPoint: interpolatePoints(bezierA.startPoint, bezierB.startPoint, progress),
                    endPoint: interpolatePoints(bezierA.endPoint, bezierB.endPoint, progress),
                    cp1: interpolatePoints(bezierA.cp1, bezierB.cp1, progress),
                    cp2: interpolatePoints(bezierA.cp2, bezierB.cp2, progress),
                }
                result.push(new Curve({
                    forcedBezier
                }));
            }
            return result;
        }

        function interpolatePoints(pointA, pointB, progress) {
            const diffX = pointA[0] - pointB[0];
            const diffY = pointA[1] - pointB[1];
            const newX = progress * diffX + pointB[0];
            const newY = progress * diffY + pointB[1];
            return [newX, newY];
        }



        function rngIfy(number, maxMag, minMag) {
            const max = number + maxMag;
            let min;
            if (typeof minMag !== 'undefined') {
                min = number - minMag;
            } else {
                min = number - maxMag;
            }
            return Math.floor(Math.random() * (max - min) + min);
        }

        function randomInRange(min, max) {
            return Math.floor(Math.random() * (max - min) + min)
        }


        // generateCurves(CURVES);

        randomizeButton = document.querySelector('#randomize');

        start();
        randomizeButton.onclick = () => start();
    </script>
</body>

</html>
