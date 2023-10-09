<?php
namespace Catpow;
class Page{
	public $uri,$path_to_root,$filename,$dir,$dir_uri,$router_uri,$info,$scripts,$styles;
	private static $instance;
	private function __construct($uri,$info){
		$this->uri=$uri;
		$this->path_to_root=str_repeat('../',substr_count(ltrim($uri,'/'),'/'));
		if($this->path_to_root===''){$this->path_to_root='./';}
		$this->filename=(substr($uri,-1)==='/')?'index':pathinfo($uri)['filename'];
		$this->dir=(substr($uri,-1)==='/')?$uri:dirname($uri).'/';
		$this->scripts=new Deps('js');
		$this->styles=new Deps('css');
		
		if(empty($info) && empty($info=$GLOBALS['sitemap'][$uri]??null)){
			$dir=(substr($uri,-1)==='/')?rtrim($uri,'/'):dirname($uri);
			do{
				if(!empty($info=$GLOBALS['sitemap'][$router_uri=$dir.'/*']??null)){
					$this->router_uri=$router_uri;
					break;
				}
				$dir=dirname($dir);
			}
			while(!empty($dir) && $dir!=='.' && $dir!=='/');
		}
		$this->info=$info;
	}
	public static function init($uri,$info=null){
		return $GLOBALS['page']=static::$instance=new static($uri,$info);
	}
	public function get_the_page_file($file){
		if(file_exists($f=ABSPATH.$this->dir.$file)){return $f;}
		if(file_exists($f=TMPL_DIR.$this->dir.$file)){return $f;}
		return false;
	}
	public function get_the_file($file){
		if(substr($file,0,1)==='/'){
			if(file_exists($f=ABSPATH.$file)){return $f;}
			if(file_exists($f=TMPL_DIR.$file)){return $f;}
			if(file_exists($f=INC_DIR.'/'.$file)){return $f;}
			return null;
		}
		if(file_exists($f=ABSPATH.$this->dir.$file)){return $f;}
		if(file_exists($f=TMPL_DIR.$this->dir.$file)){return $f;}
		if(file_exists($f=ABSPATH.'/'.$file)){return $f;}
		if(file_exists($f=TMPL_DIR.'/'.$file)){return $f;}
		if(file_exists($f=INC_DIR.'/'.$file)){return $f;}
	}
	public function get_the_latest_file($file){
		if(substr($file,0,1)==='/'){
			if(file_exists($f=ABSPATH.$file)){$f1=$f;}
			if(file_exists($f=TMPL_DIR.$file)){$f2=$f;}
			elseif(file_exists($f=INC_DIR.'/'.$file)){$f2=$f;}
		}
		else{
			if(file_exists($f=ABSPATH.$this->dir.$file)){$f1=$f;}
			if(file_exists($f=TMPL_DIR.$this->dir.$file)){$f2=$f;}
			elseif(file_exists($f=ABSPATH.'/'.$file)){$f2=$f;}
			elseif(file_exists($f=TMPL_DIR.'/'.$file)){$f2=$f;}
			elseif(file_exists($f=INC_DIR.'/'.$file)){$f2=$f;}
		}
		return empty($f2)?$f1:((empty($f1) || filemtime($f2)>filemtime($f1))?$f2:$f1);
	}
	public function get_file_path_for_uri($uri){
		if(substr($uri,0,1)==='/'){return ABSPATH.$uri;}
		return ABSPATH.$this->dir.$uri;
	}
	public function file_should_exists($file){
		if(substr($file,0,1)==='/'){
			if(
				file_exists($f=ABSPATH.$file) || 
				file_exists(TMPL_DIR.$file) || 
				file_exists(INC_DIR.'/'.$file)
			){return true;}
		}
		else{
			if(
				file_exists($f=ABSPATH.$this->dir.$file) ||
				file_exists(TMPL_DIR.$this->dir.$file)
			){return true;}
		}
		$ext=strrchr($f,'.');
		if($ext==='.js'){
			if(
				Jsx::get_jsx_file_for_file($f) ||
				Jsx::get_entry_jsx_file_for_file($f) || 
				Jsx::get_entry_tsx_file_for_file($f)
			){return true;}
		}
		if($ext==='.css'){
			if(Scss::get_scss_file_for_file($f)){return true;}
		}
		return false;
	}
	public function generate_webp_for_image($image){
		$im=$this->get_gd($image);
		if(empty($im)){return false;}
		$file=$this->get_the_latest_file($image);
		$dest_file=$this->get_file_path_for_uri($image);
		$dest_dir=dirname($dest_file);
		if(!is_dir($dest_dir)){mkdir($dest_dir,0755,true);}
		imagewebp($im,preg_replace('/\.\w+$/','.webp',$dest_file));
		if(!file_exists($dest_file) || ($file!==$dest_file && filemtime($file)>filemtime($dest_file))){
			copy($file,$dest_file);
		}
		return preg_replace('/\.\w+$/','.webp',$image);
	}
	public function get_gd($image){
		$file=$this->get_the_latest_file($image);
		if(empty($file)){return false;}
		switch(strrchr($image,'.')){
			case '.jpg':
			case '.jpeg':
				return imagecreatefromjpeg($file);
			case '.png':
				return imagecreatefrompng($file);
			case '.gif':
				return imagecreatefromgif($file);
		}
		return false;
	}
	public function use_block($block){
		if(Block::get_block_file($block,'app/index.jsx')){
			$this->scripts->enqueue('/blocks/'.$block.'/app.js');
		}
		if(Block::get_block_file($block,'script.jsx') || Block::get_block_file($block,'script.js')){
			$this->scripts->enqueue('/blocks/'.$block.'/script.js');
		}
		if(Block::get_block_file($block,'style.scss') || Block::get_block_file($block,'style.css')){
			$this->styles->enqueue('/blocks/'.$block.'/style.css');
		}
	}
	public function render_deps(){
		$this->scripts->render();
		$this->styles->render();
	}
	public function __get($name){
		if(isset($this->info[$name])){return $this->info[$name];}
	}
}