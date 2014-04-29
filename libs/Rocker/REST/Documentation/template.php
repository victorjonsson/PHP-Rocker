<?php
/**
 * @var array $config
 * @var array $documentation
 */

$appName = empty($config['application.name']) ? $_SERVER['HTTP_HOST']:$config['application.name'];
$version = empty($config['application.version']) ? '':$config['application.version'];


/**
 * @param array $data
 * @return int
 */
function findOperationsInPath($data) {
    $num = 0;
    foreach($data as $content) {
        if( !empty($content['data']) ) {
            $num++;
        } else {
            $num += findOperationsInPath($content);
        }
    }
    return $num;
}

/**
 * @param array $data
 * @param string $parent
 */
function outputOperationsInPath($data, $parent) {
    ?>
    <div class="op-container">
        <?php foreach($data as $key => $content): ?>
            <?php if( isset($content['data']) ): ?>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">
                            <?php echo ($parent=='root' ? '':$parent) .'/'. ($key==$parent ? '*':$key) ?>
                            <small>[<?php echo $content['data']['methods'] ?>]</small>
                        </h3>
                    </div>
                    <div class="panel-body">
                        <?php echo isset($content['data']['description']) ? $content['data']['description']:'' ?>
                        <hr />
                        <div class="extra-info">
                            <?php if( !empty($content['data']['links']) ): ?>
                                <p>
                                    <em><strong>More info:</strong>
                                    <?php foreach( explode(',', $content['data']['links']) as $link ): ?>
                                        <a target="_blank" href="<?php echo trim($link) ?>"><?php echo $link ?></a>
                                    <?php endforeach; ?>
                                    </em>
                                </p>
                            <?php endif; ?>
                            <p>
                                <em><strong>Class:</strong> <?php echo $content['data']['class'] ?></em>
                            </p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <h2><?php echo $parent .'/'. $key ?></h2>
                <?php outputOperationsInPath($content, $parent .'/'. $key) ?>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <?php
}

?><!DOCTYPE html>
<html lang="en-US">
<head>

    <meta charset="UTF-8" />
    <title>
        <?php echo $appName ?> - API Documentation
        <?php if( $version ) echo 'v'.$version ?>
        (Rocker v<?php echo \Rocker\Server::VERSION ?>)
    </title>

    <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css" type="text/css" />

    <style>

        body {
            padding-bottom: 20px;
        }

        .main h1 {
            padding: 15px;
            margin-top: 8px;
            margin-bottom: 0;
        }

            .main h1 span {
                float: right;
            }

            .main h1 a {
                text-decoration: none;
                display: block;
            }

            .main .panel-title small {
                margin-left: 3px;
                font-size: 75%;
            }

            .extra-info {
                color: #777;
            }

            .extra-info a:link, .extra-info a:visited {
                color: #777;
                text-decoration: underline;
            }

            .extra-info a:hover, .extra-info a:active {
                text-decoration: none;
            }

        .op-container {
            padding:10px 20px 5px;
            display: none;
            background: #F9F9f9;
        }

    </style>

</head>
<body>

    <div class="container">

        <div class="page-header">
            <h1>
                <?php echo $appName ?>
                <small><?php echo $version ? ' v'.$version:' - API Documentation'; ?></small>
            </h1>
        </div>

        <div class="main">
            <?php foreach($documentation['operations'] as $path => $data): ?>
                <h1 style="background: #F9F9F9">
                    <a href="#" class="toggler">
                        /<?php if( $path !== 'root' ) echo $path ?>
                        <small>(<?php echo findOperationsInPath($data); ?> operations)</small>
                        <span>+</span>
                    </a>
                </h1>
                <?php outputOperationsInPath($data, $path) ?>
            <?php endforeach; ?>
        </div>

    </div>

    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
    <script>
        $('a.toggler').click(function() {
            var $link = $(this),
                $container = $link.parent().next();
            $container.slideToggle(function() {
                $link.find('span').text( $container.is(':visible') ? '-':'+' );
            });
            return false;
        });
    </script>

</body>
</html>