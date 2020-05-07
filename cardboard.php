<?php
/**
 * Plugin Name: Cardboard
 * Version: 4.8.0
 * Description: This plugin enables you to enjoy 360 photo with Google Cardboard.
 * Author: Takayuki Miyauchi
 * Author URI: https://github.com/miya0001/cardboard
 * 
 * Modified by: Zeroichi-Production
 * Modified Date: Tue, 31 Mar 2020 10:17:00 +0900
 * Plugin URI: https://github.com/Zeroichi-Production/cardboard
 * Text Domain: cardboard
 * Domain Path: /languages
 * @package cardboard
 */

register_activation_hook( __FILE__, 'cardboard_activate' );

function cardboard_activate() {
	CardBoard::add_rewrite_endpoint();
	flush_rewrite_rules();
}

register_deactivation_hook( __FILE__, 'cardboard_deactivate' );

function cardboard_deactivate() {
	CardBoard::remove_rewrite_endpoint();
	flush_rewrite_rules();
}

$cardboard = new CardBoard();

class Cardboard
{
	const NS = 'http://ns.google.com/photos/1.0/panorama/';
	const QUERY_VAR = 'cardboard';

	public function __construct()
	{
		add_action( "plugins_loaded", array( $this, "plugins_loaded" ) );
	}

	public function plugins_loaded()
	{
		add_action( "init", array( $this, "init" ) );
		if ( is_admin() ) {
			add_action( "add_attachment", array( $this, "add_attachment" ) );
			add_filter( "image_send_to_editor", array( $this, "image_send_to_editor" ), 10, 8 );
		} else {
			add_action( "wp_head", array( $this, "wp_head" ) );
			add_action( "wp_enqueue_scripts", array( $this, "wp_enqueue_scripts" ) );
			add_action( "template_redirect", array( $this, "template_redirect" ) );
			add_shortcode( 'cardboard', array( $this, 'shortcode' ) );
		}
	}

	/**
	 * Shortcode
	 * @param $p
	 *
	 * @return string
	 */
	public function shortcode( $p ) {
		if ( intval( $p['id'] ) ) {
			$src = wp_get_attachment_image_src( $p['id'], 'full' );
			if ( $src ) {
				return sprintf(
					'<div class="cardboard" data-image="%s"><a class="full-screen" href="%s"><span class="dashicons dashicons-editor-expand"></span></a></div>',
					esc_url( $src[0] ),
					home_url( self::QUERY_VAR . '/' . intval( $p['id'] ) )
				);
			}
		}
	}

	/**
	 * add endpoint example.com/cardboard/1234
	 */
	public static function add_rewrite_endpoint()
	{
		add_rewrite_endpoint( self::QUERY_VAR, EP_ROOT );
	}

	/**
	 * remove endpoint example.com/cardboard/1234
	 */
	public static function remove_rewrite_endpoint()
	{
		global $wp_rewrite;
		foreach ( $wp_rewrite->endpoints as $key => $endpoint ) {
			if( $endpoint  == array( EP_ROOT, self::QUERY_VAR, self::QUERY_VAR ) ) {
				unset($wp_rewrite->endpoints[ $key ]);
			}
		};
	}

	public function template_redirect()
	{
		if ( isset( $GLOBALS['wp_query']->query[ self::QUERY_VAR ] ) ) {
			if ( intval( get_query_var( self::QUERY_VAR ) ) ) {
				$src = wp_get_attachment_image_src( get_query_var( self::QUERY_VAR ), 'full' );
				$post = get_post( get_query_var( self::QUERY_VAR ) );
				if ( $src && $post ) {
					?>
<!DOCTYPE html>

<html  <?php language_attributes(); ?>>
<head>
<title><?php echo esc_html( $post->post_title ); ?></title>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes" />
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
<style>
body {
width: 100%;
height: 100%;
background-color: #000;
color: #fff;
margin: 0px;
padding: 0;
overflow: hidden;
}
</style>
<!-- add 2020.03.31 start -->
<style>
/***************************
 * canvas
 ***************************/
.canvas_wrap {
	position:relative;
	width:100%;
	margin:0px;
	padding:0px;
	overflow:hidden;
	background-color:#000;
}
.canvas_box {
	position:fixed;
	width:100%;
	height:100%;
	margin:0px;
	padding:0px;
	top:0;
	left:0;
	overflow:hidden;
	background-color:#000;
}
#canvas_main {
	width:100%;
	height:100%;
	margin:0px;
	padding:0px;
}
/***************************
 * canvas_start
 ***************************/
.canvas_start {
	position:absolute;
	top:0;
	left:0;
	right:0;
	bottom:0;
	margin:auto;
	background-color:#0066cc;
	opacity:0.5;
	display:none;
}
.canvas_start .inner {
	position:absolute;
	top:0;
	left:0;
	right:0;
	bottom:0;
	margin:auto;
	display:table;
	width:100%;
	height:100vh;
}
.canvas_start .inner p {
	display:table-cell;
	vertical-align:middle;
	text-align:center;
	color:#ffffff;
}
</style>
</head>

<body>
<div class="canvas_wrap">
  <div class="canvas_box">
    <canvas id="canvas_main"></canvas>

    <div class="canvas_start">
      <div class="inner"><p>TOUCH START!!</p></div>
    </div>
  </div>
  <div id="scroll_area" style="height:300%;"></div>
</div>
<!-- add 2020.03.31 end -->
</body>

<script>
WebVRConfig = {};
</script>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
<script type="text/javascript" src="<?php echo plugins_url( 'three/three.min.js', __FILE__ ); ?>"></script>
<script type="text/javascript" src="<?php echo plugins_url( 'three/three-webvr.min.js', __FILE__ ); ?>"></script>
<script type="text/javascript" src="<?php echo plugins_url( 'three/three-orbit-controls.min.js', __FILE__ ); ?>"></script>
<script type="text/javascript" src="<?php echo plugins_url( 'three/DeviceOrientationControls.js', __FILE__ ); ?>"></script>
<script>
THREE.MathUtils = THREE.Math;

var renderer = new THREE.WebGLRenderer( { 
	canvas: document.getElementById("canvas_main"),// add 2020.03.31
	antialias: true 
} );
renderer.setPixelRatio( window.devicePixelRatio );
renderer.setSize( window.innerWidth, window.innerHeight );	// add 2020.03.31
//document.body.appendChild( renderer.domElement );	// del 2020.03.31

var scene, camera, controls, effect, manager;
scene = new THREE.Scene();
camera = new THREE.PerspectiveCamera( 75, window.innerWidth / window.innerHeight, 1, 100 );
//controls = new THREE.VRControls( camera );
//effect = new THREE.VREffect( renderer );
//effect.setSize( window.innerWidth, window.innerHeight );
//manager = new WebVRManager( renderer, effect, { hideButton: false } );

// add 2020.03.31 -->
var opt;
var isGyro=false;
var resGyro=false;
var cv = renderer.domElement;

vrGameBase();
function vrGameBase(optUser) {
	//options
	const optDef = {
		enableGyro : true,
		camera     : { fov : 75, near : 1, far : 100 }
	};
	//opt = $.extend(optDef,optUser);
	opt = optDef;

	//ジャイロセンサー確認
	if((opt.enableGyro)&&(window.DeviceOrientationEvent)&&('ontouchstart' in window)){
		isGyro=true;
	}
}

console.log( "isGyro: " + isGyro);
if(!isGyro){
	//PCなど非ジャイロ
	setCanvas();
}else{
	//一応ジャイロ持ちデバイス
	//ジャイロ動作確認
    window.addEventListener("deviceorientation",doGyro,false);
	function doGyro(){
		resGyro=true;
		window.removeEventListener("deviceorientation",doGyro,false);
	}

	//0.3秒後に判定
	setTimeout(function(){
		if(resGyro){
			//ジャイロが動いた
			setCanvas();

		}else{
			//ジャイロ持ってるくせに動かなかった			
			if (typeof DeviceOrientationEvent.requestPermission=== 'function') {
    			// iOS 13+
				//ユーザアクションを得るための要素を表示
				var cv_start = cv.closest(".canvas_box").querySelector(".canvas_start");
				cv_start.style.display = "block";
				cv_start.onclick = function(){
					cv_start.style.display = "none";
    				DeviceOrientationEvent.requestPermission().then(response => {
   						if (response === 'granted') {
							isGyro=true;
    	  				} else {						  
							//「動作と方向」が許可されなかった
							isGyro=false;
						}
					}).catch(console.error);
				}
				setCanvas();
    		} else {
				// non iOS 13+
				setTimeout(function() {
					if(!resGyro){
						//もう少し待ってジャイロが動かなければPC版で表示
						isGyro=false;
					}
					setCanvas();
				},1000);
    		}
		}
	},300);
}

function setCanvas(){
	//if(!Detector.webgl){Detector.addGetWebGLMessage();}
  	console.log( "isGyro: " + isGyro + " resGyro: " + resGyro);

	//スマホなどジャイロセンサーが有効なときはDeviceOrientationControlsを使う
	if(isGyro){
		//通常カメラ
		camera.position.set(0,0,0.01);
		camera.lookAt(new THREE.Vector3(0,0,0));

		controls=new THREE.DeviceOrientationControls(camera);
		controls.connect();
		controls.update();

		window.addEventListener( 'resize', onWindowResize, false );
	}else{
		//PCなどジャイロセンサーがない場合はOrbitControls
		camera.position.set(0,0,0.01);
		camera.lookAt(new THREE.Vector3(0,0,0));
		controls=new THREE.OrbitControls(camera,renderer.domElement);
//			controls.autoRotate    =false;
//			controls.enableRotate  =true;
//			controls.rotateSpeed   =-0.05;
//			controls.enableDamping =true;
//				controls.dampingFactor =0.1;
//				controls.enableZoom    =false;
//				controls.enablePan     =false;
//			}
		window.addEventListener( 'resize', onWindowResize, false );
	}
	//console.log( "setCanvas finished");
	init();
}
// <-- add 2020.03.31

//init();	// del 2020.03.31
function init() {
	var texloader = new THREE.TextureLoader();
	texloader.setCrossOrigin( "anonymous" );	// add 2020.03.31
    var sphere = new THREE.Mesh(
        new THREE.SphereGeometry( 20, 32, 24, 0 ), // Note: Math.PI * 2 = 360
        new THREE.MeshBasicMaterial( {
            map: texloader.load( '<?php echo esc_js( $src[0] ); ?>' )
        } )
    );
    sphere.scale.x = -1;

    scene.add( sphere );

    animate();
}

function animate( timestamp ) {
	controls.update();
	//	manager.render( scene, camera, timestamp );	// del 2020.03.31
	renderer.render( scene, camera );	// add 2020.03.31
    requestAnimationFrame( animate );
}

function onWindowResize() {

	camera.aspect = window.innerWidth / window.innerHeight;
	camera.updateProjectionMatrix();

	renderer.setSize( window.innerWidth, window.innerHeight );
}

</script>
</html>
					<?php
					exit;
				}
			}
			$GLOBALS['wp_query']->set_404();
			status_header( 404 );
			return;
		}
	}

	public function init()
	{
		static::add_rewrite_endpoint();
	}

	public function add_attachment( $post_id )
	{
		$src = get_attached_file( $post_id );
		if ( self::is_panorama_photo( $src ) ) {
			update_post_meta( $post_id, 'is_panorama_photo', true );
		}
	}

	public function image_send_to_editor( $html, $post_id, $caption, $title, $align, $url, $size, $alt )
	{
		if ( get_post_meta( $post_id, 'is_panorama_photo' ) && ( ! is_array( $size ) && ( 'full' === $size || 'large' === $size ) ) ) {
			return '[cardboard id="' . esc_attr( $post_id ) . '"]';
		} elseif ( get_post_meta( $post_id, 'is_panorama_photo' ) ) {
			if ( preg_match( "/\.jpg$/", $url ) ) {
				$html = str_replace( $url, esc_url( home_url( self::QUERY_VAR . '/' . $post_id ) ), $html );
			}
			return $html;
		} else {
			return $html;
		}
	}

	public function wp_head()
	{
		?>
		<style>
		.cardboard
		{
			position: relative;
		}
		.cardboard .full-screen
		{
			display: block;
			position: absolute;
			bottom: 8px;
			right: 8px;
			z-index: 999;
			color: #ffffff;
			text-decoration: none;
			border: none;
		}
		</style>
		<?php
	}

	public function wp_enqueue_scripts()
	{
		wp_enqueue_style( 'dashicons' );

		wp_enqueue_script(
			"three-js",
			plugins_url( 'three/three.min.js', __FILE__ ),
			array(),
			time(),
			true
		);
		wp_enqueue_script(
			"three-orbit-controls-js",
			plugins_url( 'three/three-orbit-controls.min.js', __FILE__ ),
			array( 'three-js' ),
			time(),
			true
		);
		wp_enqueue_script(
			"cardboard-js",
			plugins_url( 'js/cardboard.js', __FILE__ ),
			array( 'jquery','three-orbit-controls-js' ),
			time(),
			true
		);
	}

	/**
	 * Check exif and xmp meta data for detecting is it a paorama or not.
	 * @param  string  $image A path to image.
	 * @return boolean        Is image panorama photo or not.
	 */
	public static function is_panorama_photo( $image )
	{
		$content = file_get_contents( $image );
		$xmp_data_start = strpos( $content, '<x:xmpmeta' );
		$xmp_data_end   = strpos( $content, '</x:xmpmeta>' );
		$xmp_length     = $xmp_data_end - $xmp_data_start;
		if ( $xmp_length ) {
			$xmp_data = substr( $content, $xmp_data_start, $xmp_length + 12 );
			$xmp = simplexml_load_string( $xmp_data );
			$xmp = $xmp->children( "http://www.w3.org/1999/02/22-rdf-syntax-ns#" );
			$xmp = $xmp->RDF->Description;
			if ( "TRUE" === strtoupper( (string) $xmp->attributes( self::NS )->UsePanoramaViewer ) ) {
				return true;
			} elseif ( "TRUE" === strtoupper( (string) $xmp->children( self::NS )->UsePanoramaViewer ) ) {
				return true;
			}
		}

		$models = array(
			'RICOH THETA',
		);
		$models = apply_filters( 'cardboard_exif_models', $models );

		$file_type = wp_check_filetype( $image );
		if ( "image/jpeg" === $file_type['type'] ) {
			$exif = exif_read_data( $image );
			if ( $exif && ! empty( $exif['Model'] ) ) {
				foreach ( $models as $model ) {
					if ( false !== strpos( strtoupper( $exif['Model'] ), strtoupper( $model ) ) ) {
						return true;
					}
				}
			}
		}

		return false;
	}
}
