<?php
/**
 * Lightweight dependency-free PDF generator used for prototype documents.
 * It creates a simple, valid PDF without Composer/TCPDF so it works in XAMPP.
 */
class ETW_PDF {
    private $objects = [];
    private $pages = [];
    private $current = '';
    private $width = 612;  // Letter
    private $height = 792;
    private $images = [];

    public function addPage() {
        if ($this->current !== '') $this->pages[] = $this->current;
        $this->current = '';
    }
    public function text($x,$y,$text,$size=10,$bold=false) {
        $font = $bold ? '/F2' : '/F1';
        $safe = $this->escape($text);
        $this->current .= "BT {$font} {$size} Tf 1 0 0 1 {$this->fmt($x)} {$this->fmt($y)} Tm ({$safe}) Tj ET\n";
    }
    public function line($x1,$y1,$x2,$y2) {
        $this->current .= "{$this->fmt($x1)} {$this->fmt($y1)} m {$this->fmt($x2)} {$this->fmt($y2)} l S\n";
    }
    public function rect($x,$y,$w,$h,$fill=false) {
        $this->current .= "{$this->fmt($x)} {$this->fmt($y)} {$this->fmt($w)} {$this->fmt($h)} re " . ($fill?'f':'S') . "\n";
    }
    public function setStroke($r,$g,$b) { $this->current .= "{$this->fmt($r)} {$this->fmt($g)} {$this->fmt($b)} RG\n"; }
    public function setFill($r,$g,$b) { $this->current .= "{$this->fmt($r)} {$this->fmt($g)} {$this->fmt($b)} rg\n"; }
    public function polygon($points,$fill=false) {
        if (!$points) return;
        $first=$points[0]; $this->current .= $this->fmt($first[0]).' '.$this->fmt($first[1])." m\n";
        foreach(array_slice($points,1) as $p) $this->current .= $this->fmt($p[0]).' '.$this->fmt($p[1])." l\n";
        $this->current .= "h " . ($fill?'f':'S') . "\n";
    }
    /** Add an image file to the PDF and return its resource name. */
    public function image($path,$x,$y,$w,$h) {
        if (!is_file($path)) return;
        $key='Im'.(count($this->images)+1);
        $data=@file_get_contents($path);
        if ($data===false) return;
        $info=@getimagesize($path);
        if (!$info) return;
        $this->images[$key]=['data'=>$data,'width'=>(int)$info[0],'height'=>(int)$info[1],'type'=>$info['mime']];
        // Draw image with a PDF coordinate transform. JPEG is embedded directly.
        $this->current .= "q {$this->fmt($w)} 0 0 {$this->fmt($h)} {$this->fmt($x)} {$this->fmt($y)} cm /{$key} Do Q\n";
    }
    public function logo($x,$y,$w=70,$h=84) {
        // Kept for compatibility with older pages. New PDFs use the supplied NU logo image.
        $this->setFill(0.06,0.17,0.42);
        $this->setStroke(0.86,0.67,0.08);
        $pts=[[$x+$w*.08,$y+$h],[$x+$w*.92,$y+$h],[$x+$w*.86,$y+$h*.42],[$x+$w*.50,$y],[$x+$w*.14,$y+$h*.42]];
        $this->polygon($pts,true);
        $this->setFill(1,1,1); $this->text($x+$w*.25,$y+$h*.53,'NU',22,true);
        $this->setFill(0.96,0.78,0.18); $this->text($x+$w*.30,$y+$h*.24,'1900',8,true);
        $this->setFill(0.08,0.12,0.22); $this->text($x+$w+12,$y+$h*.64,'NATIONAL',13,true); $this->text($x+$w+12,$y+$h*.43,'UNIVERSITY',13,true);
    }
    public function output() {
        if ($this->current !== '') $this->pages[] = $this->current;
        if (!$this->pages) $this->addPage();

        $this->objects=[];
        $this->objects[1]='<< /Type /Catalog /Pages 2 0 R >>';
        $font1=3; $font2=4; $next=5; $kids=[];
        $imageObjs=[];
        foreach($this->images as $name=>$img){
            $imageObjs[$name]=$next++;
            $stream=$img['data'];
            $filter='/DCTDecode';
            $this->objects[$imageObjs[$name]]="<< /Type /XObject /Subtype /Image /Width {$img['width']} /Height {$img['height']} /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter {$filter} /Length ".strlen($stream)." >>\nstream\n{$stream}\nendstream";
        }
        foreach($this->pages as $stream){
            $page=$next++; $cont=$next++; $kids[]="$page 0 R";
            $xobj=''; foreach($imageObjs as $name=>$obj) $xobj.=" /{$name} {$obj} 0 R";
            $xres=$xobj!=='' ? "/XObject <<{$xobj} >>" : '';
            $this->objects[$page]="<< /Type /Page /Parent 2 0 R /MediaBox [0 0 {$this->width} {$this->height}] /Resources << /Font << /F1 {$font1} 0 R /F2 {$font2} 0 R >> {$xres} >> /Contents {$cont} 0 R >>";
            $this->objects[$cont]="<< /Length ".strlen($stream)." >>\nstream\n{$stream}endstream";
        }
        $this->objects[2]='<< /Type /Pages /Kids ['.implode(' ',$kids).'] /Count '.count($kids).' >>';
        $this->objects[$font1]='<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
        $this->objects[$font2]='<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>';

        ksort($this->objects);
        $pdf="%PDF-1.4\n%\xE2\xE3\xCF\xD3\n"; $offsets=[0]; $max=max(array_keys($this->objects));
        for($i=1;$i<=$max;$i++){ $offsets[$i]=strlen($pdf); $pdf.=$i." 0 obj\n".($this->objects[$i]??'')."\nendobj\n"; }
        $xref=strlen($pdf); $pdf.="xref\n0 ".($max+1)."\n0000000000 65535 f \n"; for($i=1;$i<=$max;$i++) $pdf.=sprintf("%010d 00000 n \n",$offsets[$i]);
        $pdf.="trailer\n<< /Size ".($max+1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";
        return $pdf;
    }
    private function escape($s){$s=(string)$s; if(function_exists('iconv')){$s=@iconv('UTF-8','Windows-1252//TRANSLIT',$s);}$s=str_replace(['\\','(',')',"\r","\n"],['\\\\','\\(','\\)', ' ',' '],$s);return $s;}
    private function fmt($n){return rtrim(rtrim(number_format((float)$n,2,'.',''),'0'),'.');}
}
function etw_pdf_wrap($text,$maxChars=82){$words=preg_split('/\s+/',trim((string)$text));$lines=[];$line='';foreach($words as $w){$test=$line===''?$w:$line.' '.$w;if(strlen($test)>$maxChars){if($line!=='')$lines[]=$line;$line=$w;}else{$line=$test;}}if($line!=='')$lines[]=$line;return $lines;}
