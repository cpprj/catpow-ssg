<?php
namespace Catpow\SVG;
use Catpow\Scss;
use Catpow\Calc;
class TriangleErosion extends Erosion{
	protected $default_atts=['fill'=>'currentColor'];
	public function render(){
		srand($this->props['seed']??1);
		$x=$this->props['x']??$this->container->x;
		$y=$this->props['y']??$this->container->y;
		$width=$this->props['width']??$this->container->width;
		$height=$this->props['height']??$this->container->height;
		$r=$this->props['r']??rand(1,5)*20;
		$p=$this->props['p']??rand(1,15);
		$m=$this->props['m']??pow(rand(0,16),2);
		$ux=$r/2;
		$uy=$r*M_SQRT3/2;
		$w=ceil($width/$ux)+1;
		$h=ceil($height/$uy)+1;
		$d='';
		foreach(self::get_cell_map($w,$h,$p,$m) as $cell){
			$is_odd=($cell[0]+$cell[1])%2===1;
			$d.=sprintf(
				$is_odd?'M %d,%d l %3$d,0 -%4$d,%5$d z ':'M %d,%d l %4$d,%5$d -%3$d,0 z ',
				$cell[0]*$ux-($ux-1)*$is_odd,
				$cell[1]*$uy-$uy/2,
				$ux*2-2,$ux-1,$uy-1
			);
		}
		printf('<path class="%s" d="%s"%s/>',$this->className,$d,$this->get_attributes());
	}
}