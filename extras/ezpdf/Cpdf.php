<?php
/**
 * Create pdf documents without additional modules
 *
 * Note that the companion class Document_CezPdf can be used to extend this class and
 * simplify the creation of documents.
 *
 * @category Documents
 * @package	 Document_Cpdf
 * @author   Wayne Munro (inactive) <pdf@ros.co.nz>
 * @author   Lars Olesen <lars@legestue.net>
 * @author   Sune Jensen <sj@sunet.dk>
 * @author   Ole K <ole1986@users.sourceforge.net>
 * @copyright 2007 - 2013 The authors
 * @license   GPL http://www.opensource.org/licenses/gpl-license.php
 * @version  0.11.6
 * @link     http://pdf-php.sf.net
 */
class Cpdf
{
	/**
	 * allow the programmer to output debug messages on serveral places
	 * 'none' = no debug output at all
	 * 'error_log' = use error_log
	 * 'variable' = store in a variable called $this->messages
	 *
	 * @default 'error_log'
	 */
	public $DEBUG = 'error_log';
	
	/**
	 * Set the debug level
	 * E_USER_ERROR = only errors
	 * E_USER_WARNING = errors and warning
	 * E_USER_NOTICE =  nearly everything
	 *
	 * @default E_USER_WARNING
	 */
	public $DEBUGLEVEL = E_USER_WARNING;
	/**
	 * Reversed char string to allow arabic or Hebrew
	 */
	public $rtl = false;
	
	/**
	 * flag to validate the output and if output method has be executed
	 */
	protected $valid = false;
	
	/**
	 * global defined temporary path used on several places
	 */
	public $tempPath = 'tmp/pdf';
    /**
     * the current number of pdf objects in the document
     *
     * @var integer
     */
    private $numObj=0;

    /**
      * this array contains all of the pdf objects, ready for final assembly
      *
      * @var array
      */
    private $objects = array();

    /**
     * allows object being hashed (affect images only)
     */
    public $hashed = true;
    /**
     * Object hash used to free pdf from redundacies (primary images)
     */
    private $objectHashes = array();
    
    /**
      * the objectId (number within the objects array) of the document catalog
      *
      * @var integer
      */
    private $catalogId;


	public $targetEncoding = 'iso-8859-1';
	/**
	 * @var boolean Whether the text passed in should be treated as Unicode or just local character set.
	 */
	public $isUnicode = false;

	/**
	 * @var boolean used to either embed or not embed ttf/pfb fonts.
	 */
	protected $embedFont = true;

	/**
     * store the information about the relationship between font families
     * this used so that the code knows which font is the bold version of another font, etc.
     * the value of this array is initialised in the constuctor function.
     *
     * @var array
     */
    private $fontFamilies = array(
    		'Helvetica' => array(
    				'b'=>'Helvetica-Bold',
    				'i'=>'Helvetica-Oblique',
    				'bi'=>'Helvetica-BoldOblique',
    				'ib'=>'Helvetica-BoldOblique',
    			),
    		'Courier' => array(
    				'b'=>'Courier-Bold',
    				'i'=>'Courier-Oblique',
    				'bi'=>'Courier-BoldOblique',
    				'ib'=>'Courier-BoldOblique',
    			),
    		'Times-Roman' => array(
    				'b'=>'Times-Bold',
    				'i'=>'Times-Italic',
    				'bi'=>'Times-BoldItalic',
    				'ib'=>'Times-BoldItalic',
    			)
    );

	/**
	 * the core fonts to ignore them from unicode
	 */
	private $coreFonts = array('courier', 'courier-bold', 'courier-oblique', 'courier-boldoblique',
    'helvetica', 'helvetica-bold', 'helvetica-oblique', 'helvetica-boldoblique',
    'times-roman', 'times-bold', 'times-italic', 'times-bolditalic',
    'symbol', 'zapfdingbats');

    /**
     * array carrying information about the fonts that the system currently knows about
     * used to ensure that a font is not loaded twice, among other things
     *
     * @var array
     */
    private $fonts = array();

    /**
      * a record of the current font
      *
      * @var string
      */
    private $currentFont='';

    /**
     * the current base font
     *
     * @var string
     */
    private $currentBaseFont='';

    /**
      * the number of the current font within the font array
      *
      * @var integer
      */
    private $currentFontNum=0;

    /**
     * @var integer
     */
    private $currentNode;

    /**
      * object number of the current page
      *
      * @var integer
      */
    private $currentPage;

    /**
      * object number of the currently active contents block
      */
    private $currentContents;

    /**
      * number of fonts within the system
      */
    private $numFonts = 0;

    /**
     * current colour for fill operations, defaults to inactive value, all three components should be between 0 and 1 inclusive when active
     */
    private $currentColour = array('r' => -1, 'g' => -1, 'b' => -1);

    /**
     * current colour for stroke operations (lines etc.)
     */
    private $currentStrokeColour = array('r' => -1, 'g' => -1, 'b' => -1);

    /**
      * current style that lines are drawn in
      */
    private $currentLineStyle='';

    /**
      * an array which is used to save the state of the document, mainly the colours and styles
      * it is used to temporarily change to another state, the change back to what it was before
      */
    private $stateStack = array();

    /**
     * number of elements within the state stack
     */
    private $nStateStack = 0;

    /**
     * number of page objects within the document
     */
    private $numPages=0;

    /**
     * object Id storage stack
     */
    private $stack=array();

    /**
     * number of elements within the object Id storage stack
     */
    private $nStack=0;

    /**
     * an array which contains information about the objects which are not firmly attached to pages
     * these have been added with the addObject function
     */
    private $looseObjects=array();

    /**
     * array contains infomation about how the loose objects are to be added to the document
     */
    private $addLooseObjects=array();

    /**
      * the objectId of the information object for the document
      * this contains authorship, title etc.
      */
    private $infoObject=0;

    /**
      * number of images being tracked within the document
      */
    private $numImages=0;

    /**
      * an array containing options about the document
      * it defaults to turning on the compression of the objects
      */
    public $options=array('compression'=>7);

    /**
      * the objectId of the first page of the document
      */
    private $firstPageId;

    /**
      * used to track the last used value of the inter-word spacing, this is so that it is known
      * when the spacing is changed.
      */
    private $wordSpaceAdjust=0;

    /**
      * track if the current font is bolded or italicised
      */
    private $currentTextState = '';

    /**
     * messages are stored here during processing, these can be selected afterwards to give some useful debug information
     */
    public $messages='';

    /**
     * the ancryption array for the document encryption is stored here
     */
    private $arc4='';

    /**
     * the object Id of the encryption information
     */
    private $arc4_objnum=0;

    /**
     * the file identifier, used to uniquely identify a pdf document
     */
    public $fileIdentifier='';

    /**
     * a flag to say if a document is to be encrypted or not
     *
     * @var boolean
     */
    private $encrypted=0;

	/**
	 * Set the encryption mode 
	 * 1 = RC40bit
	 * 2 = RC128bit (since PDF Version 1.4)
	 */
	 private $encryptionMode = 1;
    /**
     * the encryption key for the encryption of all the document content (structure is not encrypted)
     *
     * @var string
     */
    private $encryptionKey='';
	
	/*
	 * encryption padding fetched from the Adobe PDF reference
	 */
	private $encryptionPad;
    
    /**
     * array which forms a stack to keep track of nested callback functions
     *
     * @var array
     */
    private $callback = array();

    /**
     * the number of callback functions in the callback array
     *
     * @var integer
     */
    private $nCallback = 0;

    /**
     * store label->id pairs for named destinations, these will be used to replace internal links
     * done this way so that destinations can be defined after the location that links to them
     *
     * @var array
     */
    private $destinations = array();

    /**
     * store the stack for the transaction commands, each item in here is a record of the values of all the
     * variables within the class, so that the user can rollback at will (from each 'start' command)
     * note that this includes the objects array, so these can be large.
     *
     * @var string
     */
    private $checkpoint = '';

    /**
     * Constructor - starts a new document
     *
     * @param array $pageSize Array of 4 numbers, defining the bottom left and upper right corner of the page. first two are normally zero.
     *
     * @return void
     */
    public function __construct($pageSize = array(0, 0, 612, 792), $isUnicode = false)
    {
      $this->tempPath = 'tmp/'.FS_TMP_NAME.'pdf';
    	$this->isUnicode = $isUnicode;
    	// set the hardcoded encryption pad
    	$this->encryptionPad = chr(0x28).chr(0xBF).chr(0x4E).chr(0x5E).chr(0x4E).chr(0x75).chr(0x8A).chr(0x41).chr(0x64).chr(0x00).chr(0x4E).chr(0x56).chr(0xFF).chr(0xFA).chr(0x01).chr(0x08).chr(0x2E).chr(0x2E).chr(0x00).chr(0xB6).chr(0xD0).chr(0x68).chr(0x3E).chr(0x80).chr(0x2F).chr(0x0C).chr(0xA9).chr(0xFE).chr(0x64).chr(0x53).chr(0x69).chr(0x7A);
        
        $this->newDocument($pageSize);

		if ( in_array('Windows-1252', mb_list_encodings()) ) {
      		$this->targetEncoding = 'Windows-1252';
    	}
    
        // font familys are already known in $this->fontFamilies
        $this->fileIdentifier = md5('ROSPDF');
    }

    /**
     * Document object methods (internal use only)
     *
     * There is about one object method for each type of object in the pdf document
     * Each function has the same call list ($id,$action,$options).
     * $id = the object ID of the object, or what it is to be if it is being created
     * $action = a string specifying the action to be performed, though ALL must support:
     *           'new' - create the object with the id $id
     *           'out' - produce the output for the pdf object
     * $options = optional, a string or array containing the various parameters for the object
     *
     * These, in conjunction with the output function are the ONLY way for output to be produced
     * within the pdf 'file'.
     */

    /**
     * destination object, used to specify the location for the user to jump to, presently on opening
     * @access private
     */
    private function o_destination($id,$action,$options='')
    {
        if ($action!='new'){
            $o =& $this->objects[$id];
        }
        switch($action){
            case 'new':
                 $this->objects[$id]=array('t'=>'destination','info'=>array());
                 $tmp = '';
                 switch ($options['type']){
                     case 'XYZ':
                     case 'FitR':
                         $tmp =  ' '.$options['p3'].$tmp;
                     case 'FitH':
                     case 'FitV':
                     case 'FitBH':
                     case 'FitBV':
                         $tmp =  ' '.$options['p1'].' '.$options['p2'].$tmp;
                     case 'Fit':
                     case 'FitB':
                         $tmp =  $options['type'].$tmp;
                         $this->objects[$id]['info']['string']=$tmp;
                         $this->objects[$id]['info']['page']=$options['page'];
                 }
                 break;
            case 'out':
                $tmp = $o['info'];
                $res="\n".$id." 0 obj\n".'['.$tmp['page'].' 0 R /'.$tmp['string']."]\nendobj";
                return $res;
                break;
        }
    }

    /**
     * sets the viewer preferences
     * @access private
     */
    private function o_viewerPreferences($id,$action,$options='')
    {
        if ($action!='new'){
            $o =& $this->objects[$id];
        }
        switch ($action){
            case 'new':
                $this->objects[$id]=array('t'=>'viewerPreferences','info'=>array());
                break;
            case 'add':
                foreach($options as $k=>$v){
                    switch ($k){
                        case 'HideToolbar':
                        case 'HideMenubar':
                        case 'HideWindowUI':
                        case 'FitWindow':
                        case 'CenterWindow':
                        case 'DisplayDocTitle':
                        case 'NonFullScreenPageMode':
                        case 'Direction':
                            $o['info'][$k]=$v;
                            break;
                    }
                }
                break;
            case 'out':
                $res="\n".$id." 0 obj\n".'<< ';
                foreach($o['info'] as $k=>$v){
                    $res.="\n/".$k.' '.$v;
                }
                $res.="\n>>\n";
                return $res;
                break;
        }
    }

    /**
     * define the document catalog, the overall controller for the document
     * @access private
     */
    private function o_catalog($id, $action, $options = '')
    {
        if ($action!='new'){
            $o =& $this->objects[$id];
        }
        switch ($action){
            case 'new':
                $this->objects[$id]=array('t'=>'catalog','info'=>array());
                $this->catalogId=$id;
            break;
            case 'outlines':
            case 'pages':
            case 'openHere':
                $o['info'][$action]=$options;
                break;
            case 'viewerPreferences':
                if (!isset($o['info']['viewerPreferences'])){
                    $this->numObj++;
                    $this->o_viewerPreferences($this->numObj,'new');
                    $o['info']['viewerPreferences']=$this->numObj;
                }
                $vp = $o['info']['viewerPreferences'];
                $this->o_viewerPreferences($vp,'add',$options);
                break;
            case 'out':
                $res="\n".$id." 0 obj\n".'<< /Type /Catalog';
                foreach($o['info'] as $k=>$v){
                    switch($k){
                        case 'outlines':
                            $res.=' /Outlines '.$v.' 0 R';
                            break;
                        case 'pages':
                            $res.=' /Pages '.$v.' 0 R';
                            break;
                        case 'viewerPreferences':
                            $res.=' /ViewerPreferences '.$o['info']['viewerPreferences'].' 0 R';
                            break;
                        case 'openHere':
                            $res.=' /OpenAction '.$o['info']['openHere'].' 0 R';
                            break;
                    }
                }
                $res.=" >>\nendobj";
                return $res;
                break;
        }
    }

    /**
     * object which is a parent to the pages in the document
     * @access private
     */
    private function o_pages($id,$action,$options='')
    {
        if ($action!='new'){
            $o =& $this->objects[$id];
        }
        switch ($action){
            case 'new':
                $this->objects[$id]=array('t'=>'pages','info'=>array());
                $this->o_catalog($this->catalogId,'pages',$id);
                break;
            case 'page':
                if (!is_array($options)){
                    // then it will just be the id of the new page
                    $o['info']['pages'][]=$options;
                } else {
                    // then it should be an array having 'id','rid','pos', where rid=the page to which this one will be placed relative
                    // and pos is either 'before' or 'after', saying where this page will fit.
                    if (isset($options['id']) && isset($options['rid']) && isset($options['pos'])){
                        $i = array_search($options['rid'],$o['info']['pages']);
                        if (isset($o['info']['pages'][$i]) && $o['info']['pages'][$i]==$options['rid']){
                            // then there is a match make a space
                            switch ($options['pos']){
                                case 'before':
                                    $k = $i;
                                    break;
                                case 'after':
                                    $k=$i+1;
                                    break;
                                default:
                                    $k=-1;
                                    break;
                            }
                            if ($k>=0){
                                for ($j=count($o['info']['pages'])-1;$j>=$k;$j--){
                                    $o['info']['pages'][$j+1]=$o['info']['pages'][$j];
                                }
                                $o['info']['pages'][$k]=$options['id'];
                            }
                        }
                    }
                }
                break;
            case 'procset':
                $o['info']['procset']=$options;
                break;
            case 'mediaBox':
                $o['info']['mediaBox']=$options; // which should be an array of 4 numbers
                break;
            case 'font':
                $o['info']['fonts'][]=array('objNum'=>$options['objNum'],'fontNum'=>$options['fontNum']);
                break;
            case 'xObject':
                $o['info']['xObjects'][]=array('objNum'=>$options['objNum'],'label'=>$options['label']);
                break;
            case 'out':
                if (count($o['info']['pages'])){
                    $res="\n".$id." 0 obj\n<< /Type /Pages /Kids [";
                    foreach($o['info']['pages'] as $k=>$v){
                        $res.=$v." 0 R ";
                    }
                    $res.="] /Count ".count($this->objects[$id]['info']['pages']);
                    if ((isset($o['info']['fonts']) && count($o['info']['fonts'])) || isset($o['info']['procset'])){
                        $res.=" /Resources <<";
                        if (isset($o['info']['procset'])){
                            $res.=" /ProcSet ".$o['info']['procset'];
                        }
                        if (isset($o['info']['fonts']) && count($o['info']['fonts'])){
                            $res.=" /Font << ";
                            foreach($o['info']['fonts'] as $finfo){
                                $res.=" /F".$finfo['fontNum']." ".$finfo['objNum']." 0 R";
                            }
                            $res.=" >>";
                        }
                        if (isset($o['info']['xObjects']) && count($o['info']['xObjects'])){
                            $res.=" /XObject << ";
                            foreach($o['info']['xObjects'] as $finfo){
                                $res.=" /".$finfo['label']." ".$finfo['objNum']." 0 R";
                            }
                            $res.=" >>";
                        }
                        $res.=" >>";
                        if (isset($o['info']['mediaBox'])){
                            $tmp=$o['info']['mediaBox'];
                            $res.=" /MediaBox [".sprintf('%.3F',$tmp[0]).' '.sprintf('%.3F',$tmp[1]).' '.sprintf('%.3F',$tmp[2]).' '.sprintf('%.3F',$tmp[3]).']';
                        }
                    }
                    $res.=" >>\nendobj";
                } else {
                    $res="\n".$id." 0 obj\n<< /Type /Pages\n/Count 0\n>>\nendobj";
                }
                return $res;
                break;
         }
    }

    /**
     * Beta Redirection function
     * @access private
     */
	private function o_redirect($id,$action,$options=''){
        switch ($action){
            case 'new':
                $this->objects[$id]=array('t'=>'redirect','data'=>$options['data'],'info'=>array());
                $this->o_pages($this->currentNode,'xObject',array('label'=>$options['label'],'objNum'=>$id));
                break;
            case 'out':
                $o =& $this->objects[$id];
                $tmp=$o['data'];
                $res= "\n".$id." 0 obj\n<<";
                $res.="/R".$o['data']." ".$o['data']." 0 R>>\nendobj";
                return $res;
                break;
        }
    }

    /**
     * defines the outlines in the doc, empty for now
     * @access private
     */
    private function o_outlines($id,$action,$options='')
    {
        if ($action!='new'){
            $o =& $this->objects[$id];
        }
        switch ($action){
            case 'new':
                $this->objects[$id]=array('t'=>'outlines','info'=>array('outlines'=>array()));
                $this->o_catalog($this->catalogId,'outlines',$id);
                break;
            case 'outline':
                $o['info']['outlines'][]=$options;
                break;
            case 'out':
                if (count($o['info']['outlines'])){
                    $res="\n".$id." 0 obj\n<< /Type /Outlines /Kids [";
                    foreach($o['info']['outlines'] as $k=>$v){
                        $res.=$v." 0 R ";
                    }
                    $res.="] /Count ".count($o['info']['outlines'])." >>\nendobj";
                } else {
                    $res="\n".$id." 0 obj\n<< /Type /Outlines /Count 0 >>\nendobj";
                }
                return $res;
                break;
        }
    }

    /**
     * an object to hold the font description
     * @access private
     */
    private function o_font($id,$action,$options=''){
        if ($action!='new'){
            $o =& $this->objects[$id];
        }
        switch ($action){
            case 'new':
                $this->objects[$id]=array('t'=>'font','info'=>array('name'=>$options['name'], 'fontFileName' => $options['fontFileName'],'SubType'=>'Type1'));
                $fontNum=$this->numFonts;
                $this->objects[$id]['info']['fontNum']=$fontNum;
                // deal with the encoding and the differences
                if (isset($options['differences'])){
                    // then we'll need an encoding dictionary
                    $this->numObj++;
                    $this->o_fontEncoding($this->numObj,'new',$options);
                    $this->objects[$id]['info']['encodingDictionary']=$this->numObj;
                } else if (isset($options['encoding'])){
                    // we can specify encoding here
                    switch($options['encoding']){
                        case 'WinAnsiEncoding':
                        case 'MacRomanEncoding':
                        case 'MacExpertEncoding':
                            $this->objects[$id]['info']['encoding']=$options['encoding'];
                            break;
                        case 'none':
                            break;
                        default:
                            $this->objects[$id]['info']['encoding']='WinAnsiEncoding';
                            break;
                    }
                } else {
                    $this->objects[$id]['info']['encoding']='WinAnsiEncoding';
                }
                
                if ($this->fonts[$options['fontFileName']]['isUnicode']) {
			        // For Unicode fonts, we need to incorporate font data into
			        // sub-sections that are linked from the primary font section.
			        // Look at o_fontGIDtoCID and o_fontDescendentCID functions
			        // for more informaiton.
			        //
			        // All of this code is adapted from the excellent changes made to
			        // transform FPDF to TCPDF (http://tcpdf.sourceforge.net/)
			        $toUnicodeId = ++$this->numObj;
			        $this->o_contents($toUnicodeId, 'new', 'raw');
			        $this->objects[$id]['info']['toUnicode'] = $toUnicodeId;

	        		$stream = <<<EOT
/CIDInit /ProcSet findresource begin
12 dict begin
begincmap
/CIDSystemInfo <</Registry (Adobe) /Ordering (UCS) /Supplement 0 >> def
/CMapName /Adobe-Identity-UCS def
/CMapType 2 def
1 begincodespacerange
<0000> <FFFF>
endcodespacerange
1 beginbfrange
<0000> <FFFF> <0000>
endbfrange
endcmap
CMapName currentdict /CMap defineresource pop
end
end
EOT;

			        $res = "<</Length " . mb_strlen($stream, '8bit') . " >>\n";
			        $res .= "stream\n" . $stream . "\nendstream";

			        $this->objects[$toUnicodeId]['c'] = $res;

			        $cidFontId = ++$this->numObj;
			        $this->o_fontDescendentCID($cidFontId, 'new', $options);
			        $this->objects[$id]['info']['cidFont'] = $cidFontId;
				}
                // also tell the pages node about the new font
                $this->o_pages($this->currentNode,'font',array('fontNum'=>$fontNum,'objNum'=>$id));
                break;
            case 'add':
                foreach ($options as $k=>$v){
                    switch ($k){
                        case 'BaseFont':
                            $o['info']['name'] = $v;
                            break;
                        case 'FirstChar':
                        case 'LastChar':
                        case 'Widths':
                        case 'FontDescriptor':
                        case 'SubType':
                            $this->debug('o_font '.$k." : ".$v, E_USER_NOTICE);
                            $o['info'][$k] = $v;
                            break;
                    }
                }
                
                // pass values down to descendent font
      			if (isset($o['info']['cidFont'])) {
        			$this->o_fontDescendentCID($o['info']['cidFont'], 'add', $options);
      			}
                break;
            case 'out':
            	if ($this->fonts[$this->objects[$id]['info']['fontFileName']]['isUnicode']) {
			        // For Unicode fonts, we need to incorporate font data into
			        // sub-sections that are linked from the primary font section.
			        // Look at o_fontGIDtoCID and o_fontDescendentCID functions
			        // for more informaiton.
			        //
			        // All of this code is adapted from the excellent changes made to
			        // transform FPDF to TCPDF (http://tcpdf.sourceforge.net/)

		        	$res = "\n$id 0 obj\n<</Type /Font /Subtype /Type0 /BaseFont /".$o['info']['name']."";
		        	// The horizontal identity mapping for 2-byte CIDs; may be used
		        	// with CIDFonts using any Registry, Ordering, and Supplement values.
		       		$res.= " /Encoding /Identity-H /DescendantFonts [".$o['info']['cidFont']." 0 R] /ToUnicode ".$o['info']['toUnicode']." 0 R >>\n";
		        	$res.= "endobj";
				} else {
	                $res="\n".$id." 0 obj\n<< /Type /Font /Subtype /".$o['info']['SubType']." ";
	                $res.="/Name /F".$o['info']['fontNum']." ";
	                $res.="/BaseFont /".$o['info']['name']." ";
	                if (isset($o['info']['encodingDictionary'])){
	                    // then place a reference to the dictionary
	                    $res.="/Encoding ".$o['info']['encodingDictionary']." 0 R ";
	                } else if (isset($o['info']['encoding'])){
	                    // use the specified encoding
	                    $res.="/Encoding /".$o['info']['encoding']." ";
	                }
	                if (isset($o['info']['FirstChar'])){
	                    $res.="/FirstChar ".$o['info']['FirstChar']." ";
	                }
	                if (isset($o['info']['LastChar'])){
	                    $res.="/LastChar ".$o['info']['LastChar']." ";
	                }
	                if (isset($o['info']['Widths'])){
	                    $res.="/Widths ".$o['info']['Widths']." 0 R ";
	                }
	                if (isset($o['info']['FontDescriptor'])){
	                    $res.="/FontDescriptor ".$o['info']['FontDescriptor']." 0 R ";
	                }
	                $res.=">>\nendobj";
	            }
                return $res;
                break;
        }
    }

    /**
     * a font descriptor, needed for including additional fonts
     * @access private
     */
    private function o_fontDescriptor($id, $action, $options = '')
    {
        if ($action!='new'){
            $o =& $this->objects[$id];
        }
        switch ($action){
            case 'new':
                $this->objects[$id]=array('t'=>'fontDescriptor','info'=>$options);
                break;
            case 'out':
                $res="\n".$id." 0 obj\n<< /Type /FontDescriptor ";
                foreach ($o['info'] as $label => $value){
                    switch ($label){
                        case 'Ascent':
                        case 'CapHeight':
                        case 'Descent':
                        case 'Flags':
                        case 'ItalicAngle':
                        case 'StemV':
                        case 'AvgWidth':
                        case 'Leading':
                        case 'MaxWidth':
                        case 'MissingWidth':
                        case 'StemH':
                        case 'XHeight':
                        case 'CharSet':
                            if (strlen($value)){
                                $res.='/'.$label.' '.$value." ";
                            }
                            break;
                        case 'FontFile':
                        case 'FontFile2':
                        case 'FontFile3':
                            $res.='/'.$label.' '.$value." 0 R ";
                            break;
                        case 'FontBBox':
                            $res.='/'.$label.' ['.$value[0].' '.$value[1].' '.$value[2].' '.$value[3]."] ";
                            break;
                        case 'FontName':
                            $res.='/'.$label.' /'.$value." ";
                            break;
                    }
                }
                $res.=">>\nendobj";
                return $res;
                break;
        }
    }

    /**
     * the font encoding
     * @access private
     */
    private function o_fontEncoding($id,$action,$options=''){
        if ($action!='new'){
            $o =& $this->objects[$id];
        }
        switch ($action){
            case 'new':
                // the options array should contain 'differences' and maybe 'encoding'
                $this->objects[$id]=array('t'=>'fontEncoding','info'=>$options);
                break;
            case 'out':
                $res="\n".$id." 0 obj\n<< /Type /Encoding ";
                if (!isset($o['info']['encoding'])){
                    $o['info']['encoding']='WinAnsiEncoding';
                }
                if ($o['info']['encoding']!='none'){
                    $res.="/BaseEncoding /".$o['info']['encoding']." ";
                }
                $res.="/Differences [";
                $onum=-100;
                foreach($o['info']['differences'] as $num=>$label){
                    if ($num!=$onum+1){
                        // we cannot make use of consecutive numbering
                        $res.= " ".$num." /".$label;
                    } else {
                        $res.= " /".$label;
                    }
                    $onum=$num;
                }
                $res.="] >>\nendobj";
                return $res;
                break;
        }
    }

	/**
	 * a descendent cid font, needed for unicode fonts
	 * @access private
	 */
	private function o_fontDescendentCID($id, $action, $options = '') {
		if ($action !== 'new') {
		  $o = & $this->objects[$id];
		}

		switch ($action) {
		case 'new':
		  $this->objects[$id] = array('t' => 'fontDescendentCID', 'info' => $options);

		  // we need a CID system info section
		  $cidSystemInfoId = ++$this->numObj;
		  $this->o_contents($cidSystemInfoId, 'new', 'raw');
		  $this->objects[$id]['info']['cidSystemInfo'] = $cidSystemInfoId;
		  $res = "<</Registry (Adobe)"; // A string identifying an issuer of character collections
		  $res.= " /Ordering (UCS)"; // A string that uniquely names a character collection issued by a specific registry
		  $res.= " /Supplement 0"; // The supplement number of the character collection.
		  $res.= " >>";
		  $this->objects[$cidSystemInfoId]['c'] = $res;

		  // and a CID to GID map
		  if($this->embedFont){
		  	$cidToGidMapId = ++$this->numObj;
		  	$this->o_fontGIDtoCIDMap($cidToGidMapId, 'new', $options);
		  	$this->objects[$id]['info']['cidToGidMap'] = $cidToGidMapId;
		  }
		  break;

		case 'add':
		  foreach ($options as $k => $v) {
		    switch ($k) {
		    case 'BaseFont':
		      $o['info']['name'] = $v;
		      break;

		    case 'FirstChar':
		    case 'LastChar':
		    case 'MissingWidth':
		    case 'FontDescriptor':
		    case 'SubType':
		      $this->debug("o_fontDescendentCID $k : $v", E_USER_NOTICE);
		      $o['info'][$k] = $v;
		      break;
		    }
		  }

		  // pass values down to cid to gid map
		  if($this->embedFont){
		  	$this->o_fontGIDtoCIDMap($o['info']['cidToGidMap'], 'add', $options);
		  }
		  break;

		case 'out':
		  $res = "\n$id 0 obj\n";
		  $res.= "<</Type /Font /Subtype /CIDFontType2 /BaseFont /".$o['info']['name']." /CIDSystemInfo ".$o['info']['cidSystemInfo']." 0 R";
		  if (isset($o['info']['FontDescriptor'])) {
		    $res.= " /FontDescriptor ".$o['info']['FontDescriptor']." 0 R";
		  }

		  if (isset($o['info']['MissingWidth'])) {
		    $res.= " /DW ".$o['info']['MissingWidth']."";
		  }

		  if (isset($o['info']['fontFileName']) && isset($this->fonts[$o['info']['fontFileName']]['CIDWidths'])) {
		    $cid_widths = &$this->fonts[$o['info']['fontFileName']]['CIDWidths'];
		    $w = '';
		    foreach ($cid_widths as $cid => $width) {
		      $w .= "$cid [$width] ";
		    }
		    $res.= " /W [$w]";
		  }
		  
		  if($this->embedFont){
		  	$res.= " /CIDToGIDMap ".$o['info']['cidToGidMap']." 0 R";
		  }
		  $res.= "  >>\n";
		  $res.= "endobj";

		  return $res;
		}
	}

	/**
	  * a font glyph to character map, needed for unicode fonts
	  * @access private
	  */
	private function o_fontGIDtoCIDMap($id, $action, $options = '') {
	    if ($action !== 'new') {
	      $o = & $this->objects[$id];
	    }

	    switch ($action) {
	    case 'new':
	      $this->objects[$id] = array('t' => 'fontGIDtoCIDMap', 'info' => $options);
	      break;

	    case 'out':
	      $res = "\n$id 0 obj\n";
	      $fontFileName = $o['info']['fontFileName'];
	      $tmp = $this->fonts[$fontFileName]['CIDtoGID'] = base64_decode($this->fonts[$fontFileName]['CIDtoGID']);
	      
	      if (isset($o['raw'])) {
	        $res.= $tmp;
	      } else {
	        $res.= "<<";
	        if (function_exists('gzcompress') && $this->options['compression']) {
	          // then implement ZLIB based compression on this content stream
	          $tmp = gzcompress($tmp, $this->options['compression']);
	          $res.= " /Filter /FlateDecode";
	        }
	        
	        $res.= " /Length ".mb_strlen($tmp, '8bit') .">>\nstream\n$tmp\nendstream";
	      }

	      $res.= "\nendobj";
	      return $res;
	    }
	}

    /**
     * define the document information
     * @access private
     */
    private function o_info($id,$action,$options=''){
        if ($action!='new'){
            $o =& $this->objects[$id];
        }
        switch ($action){
        case 'new':
            $this->infoObject=$id;
            $date='D:'.date('Ymd');
            $this->objects[$id]=array('t'=>'info','info'=>array('Creator'=>'R and OS php pdf writer, http://www.ros.co.nz','CreationDate'=>$date));
            break;
        case 'Title':
        case 'Author':
        case 'Subject':
        case 'Keywords':
        case 'Creator':
        case 'Producer':
        case 'CreationDate':
        case 'ModDate':
        case 'Trapped':
            $o['info'][$action]=$options;
            break;
        case 'out':
            if ($this->encrypted){
                $this->encryptInit($id);
            }
            $res="\n".$id." 0 obj\n<< ";
            foreach ($o['info']  as $k=>$v){
                $res.='/'.$k.' (';
                if ($this->encrypted){
                    $res.=$this->filterText($this->ARC4($v), true, false);
                } else {
                    $res.=$this->filterText($v, true, false);
                }
                $res.=") ";
            }
            $res.=">>\nendobj";
            return $res;
            break;
        }
    }

    /**
     * an action object, used to link to URLS initially
     * @access private
     */
    private function o_action($id,$action,$options=''){
        if ($action!='new'){
            $o =& $this->objects[$id];
        }
        switch ($action){
        case 'new':
            if (is_array($options)){
                $this->objects[$id]=array('t'=>'action','info'=>$options,'type'=>$options['type']);
            } else {
                // then assume a URI action
                $this->objects[$id]=array('t'=>'action','info'=>$options,'type'=>'URI');
            }
            break;
        case 'out':
            if ($this->encrypted){
                $this->encryptInit($id);
            }
            $res="\n".$id." 0 obj\n<< /Type /Action";
            switch($o['type']){
            case 'ilink':
                // there will be an 'label' setting, this is the name of the destination
                $res.=" /S /GoTo /D ".$this->destinations[(string)$o['info']['label']]." 0 R";
                break;
            case 'URI':
                $res.=" /S /URI /URI (";
                if ($this->encrypted){
                    $res.=$this->filterText($this->ARC4($o['info']), true, false);
                } else {
                    $res.=$this->filterText($o['info'], true, false);
                }
                $res.=")";
                break;
            }
            $res.=" >>\nendobj";
            return $res;
            break;
        }
    }

    /**
     * an annotation object, this will add an annotation to the current page.
     * initially will support just link annotations
     * @access private
     */
    private function o_annotation($id,$action,$options=''){
        if ($action!='new'){
            $o =& $this->objects[$id];
        }
        switch ($action){
        case 'new':
            // add the annotation to the current page
            $pageId = $this->currentPage;
            $this->o_page($pageId,'annot',$id);
            // and add the action object which is going to be required
            switch($options['type']){
            case 'link':
                $this->objects[$id]=array('t'=>'annotation','info'=>$options);
                $this->numObj++;
                $this->o_action($this->numObj,'new',$options['url']);
                $this->objects[$id]['info']['actionId']=$this->numObj;
                break;
            case 'ilink':
                // this is to a named internal link
                $label = $options['label'];
                $this->objects[$id]=array('t'=>'annotation','info'=>$options);
                $this->numObj++;
                $this->o_action($this->numObj,'new',array('type'=>'ilink','label'=>$label));
                $this->objects[$id]['info']['actionId']=$this->numObj;
                break;
            }
            break;
        case 'out':
			$res="\n".$id." 0 obj << /Type /Annot";
            switch($o['info']['type']){
                case 'link':
                case 'ilink':
                    $res.= " /Subtype /Link";
                    break;
            }
            $res.=" /A ".$o['info']['actionId']." 0 R";
            $res.=" /Border [0 0 0]";
            $res.=" /H /I";
            $res.=" /Rect [ ";
            foreach($o['info']['rect'] as $v){
                $res.= sprintf("%.4f ",$v);
            }
            $res.="]";
            $res.=" >>\nendobj";
            return $res;
            break;
        }
    }

    /**
     * a page object, it also creates a contents object to hold its contents
     * @access private
     */
    private function o_page($id,$action,$options=''){
        if ($action!='new'){
            $o =& $this->objects[$id];
        }
        switch ($action){
        case 'new':
            $this->numPages++;
            $this->objects[$id]=array('t'=>'page','info'=>array('parent'=>$this->currentNode,'pageNum'=>$this->numPages));
            if (is_array($options)){
                // then this must be a page insertion, array shoudl contain 'rid','pos'=[before|after]
                $options['id']=$id;
                $this->o_pages($this->currentNode,'page',$options);
            } else {
                $this->o_pages($this->currentNode,'page',$id);
            }
            $this->currentPage=$id;
            // make a contents object to go with this page
            $this->numObj++;
            $this->o_contents($this->numObj,'new',$id);
            $this->currentContents=$this->numObj;
            $this->objects[$id]['info']['contents']=array();
            $this->objects[$id]['info']['contents'][]=$this->numObj;
            $match = ($this->numPages%2 ? 'odd' : 'even');
            foreach($this->addLooseObjects as $oId=>$target){
                if ($target=='all' || $match==$target){
                    $this->objects[$id]['info']['contents'][]=$oId;
                }
            }
            break;
        case 'content':
            $o['info']['contents'][]=$options;
            break;
        case 'annot':
            // add an annotation to this page
            if (!isset($o['info']['annot'])){
                $o['info']['annot']=array();
            }
            // $options should contain the id of the annotation dictionary
            $o['info']['annot'][]=$options;
            break;
        case 'out':
            $res="\n".$id." 0 obj\n<< /Type /Page";
            $res.=" /Parent ".$o['info']['parent']." 0 R";
            if (isset($o['info']['annot'])){
                $res.=" /Annots [";
                foreach($o['info']['annot'] as $aId){
                    $res.=" ".$aId." 0 R";
                }
                $res.=" ]";
            }
            $count = count($o['info']['contents']);
            if ($count==1){
                $res.=" /Contents ".$o['info']['contents'][0]." 0 R";
            } else if ($count>1){
                $res.=" /Contents [ ";
                foreach ($o['info']['contents'] as $cId){
                    $res.=$cId." 0 R ";
                }
                $res.="]";
            }
            $res.=" >>\nendobj";
            return $res;
            break;
        }
    }

    /**
     * the contents objects hold all of the content which appears on pages
     * @access private
     */
    private function o_contents($id,$action,$options=''){
        if ($action!='new'){
            $o =& $this->objects[$id];
        }
        switch ($action){
        case 'new':
            $this->objects[$id]=array('t'=>'contents','c'=>'','info'=>array());
            if (strlen($options) && intval($options)){
                // then this contents is the primary for a page
                $this->objects[$id]['onPage']=$options;
            } else if ($options=='raw'){
                // then this page contains some other type of system object
                $this->objects[$id]['raw']=1;
            }
            break;
        case 'add':
            // add more options to the decleration
            foreach ($options as $k=>$v){
                $o['info'][$k]=$v;
            }
        case 'out':
            $tmp=$o['c'];
            $res= "\n".$id." 0 obj\n";
            if (isset($this->objects[$id]['raw'])){
                $res.=$tmp;
            } else {
                $res.= "<<";
                if (function_exists('gzcompress') && $this->options['compression']){
                    // then implement ZLIB based compression on this content stream
                    $res.=" /Filter /FlateDecode";
                    $tmp = gzcompress($tmp, $this->options['compression']);
                }
                if ($this->encrypted){
                    $this->encryptInit($id);
                   $tmp = $this->ARC4($tmp);
                }
                foreach($o['info'] as $k=>$v){
                    $res .= " /".$k.' '.$v;
                }
                $res.=" /Length ".strlen($tmp)." >> stream\n".$tmp."\nendstream";
            }
            $res.="\nendobj";
            return $res;
            break;
        }
    }

    /**
     * an image object, will be an XObject in the document, includes description and data
     * @access private
     */
    private function o_image($id,$action,$options=''){
        if ($action!='new'){
            $o =& $this->objects[$id];
        }
        switch($action){
        case 'new':
    		// make the new object
            $this->objects[$id]=array('t'=>'image','data'=>$options['data'],'info'=>array());
            $this->objects[$id]['info']['Type']='/XObject';
            $this->objects[$id]['info']['Subtype']='/Image';
            $this->objects[$id]['info']['Width']=$options['iw'];
            $this->objects[$id]['info']['Height']=$options['ih'];
            if (!isset($options['type']) || $options['type']=='jpg'){
                if (!isset($options['channels'])){
                    $options['channels']=3;
                }
                switch($options['channels']){
                case 1:
                    $this->objects[$id]['info']['ColorSpace']='/DeviceGray';
                    break;
                default:
                    $this->objects[$id]['info']['ColorSpace']='/DeviceRGB';
                    break;
                }
                $this->objects[$id]['info']['Filter']='/DCTDecode';
                $this->objects[$id]['info']['BitsPerComponent']=8;
            } else if ($options['type']=='png'){
                if (strlen($options['pdata'])){
                    $this->numObj++;
                    $this->objects[$this->numObj]=array('t'=>'image','c'=>'','info'=>array());
                    $this->objects[$this->numObj]['info'] = array('Type'=>'/XObject', 'Subtype'=>'/Image', 'Width'=> $options['iw'], 'Height'=> $options['ih'], 'Filter'=>'/FlateDecode', 'ColorSpace'=>'/DeviceGray', 'BitsPerComponent'=>'8', 'DecodeParms'=>'<< /Predictor 15 /Colors 1 /BitsPerComponent 8 /Columns '.$options['iw'].' >>');
                    $this->objects[$this->numObj]['data']=$options['pdata'];
                    if (isset($options['transparency'])){
                        switch($options['transparency']['type']){
                        case 'indexed':
                            $tmp=' [ '.$options['transparency']['data'].' '.$options['transparency']['data'].'] ';
                            $this->objects[$id]['info']['Mask'] = $tmp;
                            $this->objects[$id]['info']['ColorSpace'] = ' [ /Indexed /DeviceRGB '.(strlen($options['pdata'])/3-1).' '.$this->numObj.' 0 R ]';
                            break;
                        case 'alpha':
                            $this->objects[$id]['info']['SMask'] = $this->numObj.' 0 R';
                            $this->objects[$id]['info']['ColorSpace'] = '/'.$options['color'];
                        	break;
                        }
                    }
                } else {
                    $this->objects[$id]['info']['ColorSpace']='/'.$options['color'];
                }
                $this->objects[$id]['info']['BitsPerComponent']=$options['bitsPerComponent'];
                $this->objects[$id]['info']['Filter']='/FlateDecode';
                $this->objects[$id]['data'] = $options['data'];
                $this->objects[$id]['info']['DecodeParms']='<< /Predictor 15 /Colors '.$options['ncolor'].' /Columns '.$options['iw'].' /BitsPerComponent '.$options['bitsPerComponent'].'>>';
            }
            // assign it a place in the named resource dictionary as an external object, according to
            // the label passed in with it.
            $this->o_pages($this->currentNode,'xObject',array('label'=>$options['label'],'objNum'=>$id));
            break;
        case 'out':
            $tmp=$o['data'];
            $res= "\n".$id." 0 obj\n<<";
            foreach($o['info'] as $k=>$v){
                $res.=" /".$k.' '.$v;
            }
            if ($this->encrypted){
                $this->encryptInit($id);
                $tmp = $this->ARC4($tmp);
            }
            $res.=" /Length ".strlen($tmp)." >> stream\n".$tmp."\nendstream\nendobj";
            return $res;
            break;
        }
    }

    /**
     * encryption object.
     * @access private
     */
    private function o_encryption($id,$action,$options=''){
        if ($action!='new'){
            $o =& $this->objects[$id];
        }
        switch($action){
        case 'new':
            // make the new object
            $this->objects[$id]=array('t'=>'encryption','info'=>$options);
            $this->arc4_objnum=$id;
            
            // Pad or truncate the owner password
            $owner = substr($options['owner'].$this->encryptionPad,0,32);
            $user = substr($options['user'].$this->encryptionPad,0,32);
            
            $this->debug("o_encryption: user password (".$options['user'].") / owner password (".$options['owner'].")");
            
            // convert permission set into binary string
            $permissions = sprintf("%c%c%c%c", ($options['p'] & 255),  (($options['p'] >> 8) & 255) , (($options['p'] >> 16) & 255),  (($options['p'] >> 24) & 255));
            
            // Algo 3.3 Owner Password being set into /O Dictionary
            $this->objects[$id]['info']['O'] = $this->encryptOwner($owner, $user); 
            
            // Algo 3.5 User Password - START
            $this->objects[$id]['info']['U'] = $this->encryptUser($user, $this->objects[$id]['info']['O'], $permissions);
            // encryption key is set in encryptUser function
            //$this->encryptionKey = $encryptionKey;
            
            $this->encrypted=1;
            break;
        case 'out':
            $res= "\n".$id." 0 obj\n<<";
            $res.=' /Filter /Standard';
            if($this->encryptionMode > 1){ // RC4 128bit encryption
            	$res.=' /V 2';
            	$res.=' /R 3';
            	$res.=' /Length 128';
            } else { // RC4 40bit encryption
            	$res.=' /V 1';
            	$res.=' /R 2';
            }
            // use hex string instead of char code - char codes can make troubles (E.g. CR or LF)
            $res.=' /O <'.$this->strToHex($o['info']['O']).'>';
            $res.=' /U <'.$this->strToHex($o['info']['U']).'>';
            // and the p-value needs to be converted to account for the twos-complement approach
            //$o['info']['p'] = (($o['info']['p'] ^ 0xFFFFFFFF)+1)*-1;
            $res.=' /P '.($o['info']['p']);
            $res.=" >>\nendobj";
            return $res;
            break;
        }
    }
	
    /**
     * owner part of the encryption
     * @param $owner - owner password plus padding
     * @param $user - user password plus padding
     * @access private
     */
	private function encryptOwner($owner, $user){
		$keylength = 5;
		if($this->encryptionMode > 1){
			$keylength = 16;
		}
		
        $ownerHash = $this->md5_16($owner); // PDF 1.4 - repeat this 50 times in revision 3
        if($this->encryptionMode > 1) { // if it is the RC4 128bit encryption
        	for($i = 0; $i < 50; $i++){
        		$ownerHash = $this->md5_16($ownerHash);
        	}
        }
        
        $ownerKey = substr($ownerHash,0,$keylength); // PDF 1.4 - Create the encryption key (IMPORTANT: need to check Length)
        
        $this->ARC4_init($ownerKey); // 5 bytes of the encryption key (hashed 50 times)
        $ovalue=$this->ARC4($user); // PDF 1.4 - Encrypt the padded user password using RC4
        
        if($this->encryptionMode > 1){
            $len = strlen($ownerKey);
            for($i = 1;$i<=19; ++$i){
            	$ek = '';
            	for($j=0; $j < $len; $j++){
            		$ek .= chr( ord($ownerKey[$j]) ^ $i );
            	}
            	$this->ARC4_init($ek);
            	$ovalue = $this->ARC4($ovalue);
            }
        }
        return $ovalue;
	}
	
	/**
	 * 
	 * user part of the encryption
	 * @param $user - user password plus padding
	 * @param $ownerDict - encrypted owner entry
	 * @param $permissions - permission set (print, copy, modify, ...)
	 */
	function encryptUser($user,$ownerDict, $permissions){
		$keylength = 5;
		if($this->encryptionMode > 1){
			$keylength = 16;
		}
		// make hash with user, encrypted owner, permission set and fileIdentifier
        $hash = $this->md5_16($user.$ownerDict.$permissions.$this->hexToStr($this->fileIdentifier));
        
        // loop thru the hash process when it is revision 3 of encryption routine (usually RC4 128bit)
        if($this->encryptionMode > 1) {
	        for ($i = 0; $i < 50; ++$i) {
	        	$hash = $this->md5_16(substr($hash, 0, $keylength)); // use only length of encryption key from the previous hash
			}
		}
		
        $this->encryptionKey = substr($hash,0,$keylength); // PDF 1.4 - Create the encryption key (IMPORTANT: need to check Length)
        
        if($this->encryptionMode > 1){ // if it is the RC4 128bit encryption
        	// make a md5 hash from padding string (hardcoded by Adobe) and the fileIdenfier
        	$userHash = $this->md5_16($this->encryptionPad.$this->hexToStr($this->fileIdentifier));
        	
        	// encrypt the hash from the previous method by using the encryptionKey
        	$this->ARC4_init($this->encryptionKey);
        	$uvalue=$this->ARC4($userHash);
        	
        	$len = strlen($this->encryptionKey);
            for($i = 1;$i<=19; ++$i){
            	$ek = '';
            	for($j=0; $j< $len; $j++){
            		$ek .= chr( ord($this->encryptionKey[$j]) ^ $i );
            	}
            	$this->ARC4_init($ek);
            	$uvalue = $this->ARC4($uvalue);
            }
            $uvalue .= substr($this->encryptionPad,0,16);
            
            //$this->encryptionKey = $encryptionKey;
        }else{ // if it is the RC4 40bit encryption
        	$this->ARC4_init($this->encryptionKey);
        	//$this->encryptionKey = $encryptionKey;
        	//$this->encrypted=1;
        	$uvalue=$this->ARC4($this->encryptionPad);
        }
        return $uvalue;
	}
	
	/**
	 * internal method to convert string to hexstring (used for owner and user dictionary)
	 * @param $string - any string value
	 * @access protected
	 */
	protected function strToHex($string)
	{
		$hex = '';
		for ($i=0; $i < strlen($string); $i++)
			$hex .= sprintf("%02x",ord($string[$i]));
		return $hex;
	}
	
	protected function hexToStr($hex)
	{
		$str = '';
    	for($i=0;$i<strlen($hex);$i+=2)
    	$str .= chr(hexdec(substr($hex,$i,2)));
    	return $str;
  	}

    /**
     * calculate the 16 byte version of the 128 bit md5 digest of the string
     * @access private
     */
    private function md5_16($string){
        $tmp = md5($string);
        $out = pack("H*", $tmp);
        return $out;
    }

    /**
     * initialize the encryption for processing a particular object
     * @access private
     */
    private function encryptInit($id){
        $tmp = $this->encryptionKey;
        $hex = dechex($id);
        if (strlen($hex)<6){
            $hex = substr('000000',0,6-strlen($hex)).$hex;
        }
        $tmp.= chr(hexdec(substr($hex,4,2))).chr(hexdec(substr($hex,2,2))).chr(hexdec(substr($hex,0,2))).chr(0).chr(0);
        $key = $this->md5_16($tmp);
        if($this->encryptionMode > 1){
        	$this->ARC4_init(substr($key,0,16)); // use max 16 bytes for RC4 128bit encryption key
        } else {
        	$this->ARC4_init(substr($key,0,10)); // use (n + 5 bytes) for RC4 40bit encryption key
        }
    }

    /**
     * initialize the ARC4 encryption
     * @access private
     */
    private function ARC4_init($key=''){
        $this->arc4 = '';
        // setup the control array
        if (strlen($key)==0){
            return;
        }
        $k = '';
        while(strlen($k)<256){
            $k.=$key;
        }
        $k=substr($k,0,256);
        for ($i=0;$i<256;$i++){
            $this->arc4 .= chr($i);
        }
        $j=0;
        for ($i=0;$i<256;$i++){
            $t = $this->arc4[$i];
            $j = ($j + ord($t) + ord($k[$i]))%256;
            $this->arc4[$i]=$this->arc4[$j];
            $this->arc4[$j]=$t;
        }
    }

    /**
     * ARC4 encrypt a text string
     * @access private
     */
    private function ARC4($text){
        $len=strlen($text);
        $a=0;
        $b=0;
        $c = $this->arc4;
        $out='';
        for ($i=0;$i<$len;$i++){
            $a = ($a+1)%256;
            $t= $c[$a];
            $b = ($b+ord($t))%256;
            $c[$a]=$c[$b];
            $c[$b]=$t;
            $k = ord($c[(ord($c[$a])+ord($c[$b]))%256]);
            $out.=chr(ord($text[$i]) ^ $k);
        }
		return $out;
    }

    /**
     * add a link in the document to an external URL
     * @access public
     */
    public function addLink($url,$x0,$y0,$x1,$y1){
        $this->numObj++;
        $info = array('type'=>'link','url'=>$url,'rect'=>array($x0,$y0,$x1,$y1));
        $this->o_annotation($this->numObj,'new',$info);
    }

    /**
     * add a link in the document to an internal destination (ie. within the document)
     * @access public
     */
    public function addInternalLink($label,$x0,$y0,$x1,$y1){
        $this->numObj++;
        $info = array('type'=>'ilink','label'=>$label,'rect'=>array($x0,$y0,$x1,$y1));
        $this->o_annotation($this->numObj,'new',$info);
    }

    /**
     * set the encryption of the document
     * can be used to turn it on and/or set the passwords which it will have.
     * also the functions that the user will have are set here, such as print, modify, add
     * @access public
     */
    public function setEncryption($userPass = '',$ownerPass = '',$pc = array(), $mode = 1){
    	if($mode > 1){
        	$p=bindec('01111111111111111111000011000000'); // revision 3 is using bit 3 - 6 AND 9 - 12
        }else{
        	$p=bindec('01111111111111111111111111000000'); // while revision 2 is using bit 3 - 6 only
        }
        
        $options = array(
            'print'=>4
            ,'modify'=>8
            ,'copy'=>16
            ,'add'=>32
            ,'fill'=>256
            ,'extract'=>512
            ,'assemble'=>1024
            ,'represent'=>2048
        );
        foreach($pc as $k=>$v){
            if ($v && isset($options[$k])){
                $p+=$options[$k];
            } else if (isset($options[$v])){
                $p+=$options[$v];
            }
        }
        
        // set the encryption mode to either RC4 40bit or RC4 128bit
        $this->encryptionMode = $mode;
        
        // implement encryption on the document
        if ($this->arc4_objnum == 0){
            // then the block does not exist already, add it.
            $this->numObj++;
            if (strlen($ownerPass)==0){
                $ownerPass=$userPass;
            }
            $this->o_encryption($this->numObj,'new',array('user'=>$userPass,'owner'=>$ownerPass,'p'=>$p));
        }
    }

    /**
     * should be used for internal checks, not implemented as yet
     * @access public
     */
    function checkAllHere() {
    	// set the validation flag to true when everything is ok.
    	// currently it only checks if output function has been called
    	$this->valid = true;
    }

    /**
     * return the pdf stream as a string returned from the function
     * This method is protect to force user to use ezOutput from Cezpdf.php
     * @access protected
     */
    function output($debug=0){

        if ($debug){
            // turn compression off
            $this->options['compression']=0;
        }

        if ($this->arc4_objnum){
            $this->ARC4_init($this->encryptionKey);
        }
        
		if($this->valid){
			$this->debug('The output method has been executed again', E_USER_WARNING);
		}

        $this->checkAllHere();

        $xref=array();
        $content="%PDF-1.4\n%����";
        //  $content="%PDF-1.3\n";
        $pos=strlen($content);
        foreach($this->objects as $k=>$v){
            $tmp='o_'.$v['t'];
            $cont=$this->$tmp($k,'out');
            $content.=$cont;
            $xref[]=$pos;
            $pos+=strlen($cont);
        }
        ++$pos;
        $content.="\nxref\n0 ".(count($xref)+1)."\n0000000000 65535 f \n";
        foreach($xref as $p){
            $content.=substr('0000000000',0,10-strlen($p+1)).($p+1)." 00000 n \n";
        }
        $content.="trailer\n<< /Size ".(count($xref)+1)." /Root 1 0 R /Info ".$this->infoObject." 0 R";
        // if encryption has been applied to this document then add the marker for this dictionary
        if ($this->arc4_objnum > 0){
            $content .= " /Encrypt ".$this->arc4_objnum." 0 R";
        }
        if (strlen($this->fileIdentifier)){
            $content .= " /ID[<".$this->fileIdentifier."><".$this->fileIdentifier.">]";
        }
        $content .= " >>\nstartxref\n".$pos."\n%%EOF\n";
        return $content;
    }

    /**
     * intialize a new document
     * if this is called on an existing document results may be unpredictable, but the existing document would be lost at minimum
     * this function is called automatically by the constructor function
     *
     * @access protected
     */
    protected function newDocument($pageSize=array(0,0,612,792)){
        $this->numObj=0;
        $this->objects = array();

        $this->numObj++;
        $this->o_catalog($this->numObj,'new');

        $this->numObj++;
        $this->o_outlines($this->numObj,'new');

        $this->numObj++;
        $this->o_pages($this->numObj,'new');

        $this->o_pages($this->numObj,'mediaBox',$pageSize);
        $this->currentNode = 3;

        $this->o_pages($this->numObj, 'procset', '[/PDF/TEXT/ImageB/ImageC/ImageI]');

        $this->numObj++;
        $this->o_info($this->numObj,'new');

        $this->numObj++;
        $this->o_page($this->numObj,'new');

        // need to store the first page id as there is no way to get it to the user during
        // startup
        $this->firstPageId = $this->currentContents;
    }

    /**
     * open the font file and return a php structure containing it.
     * first check if this one has been done before and saved in a form more suited to php
     * note that if a php serialized version does not exist it will try and make one, but will
     * require write access to the directory to do it... it is MUCH faster to have these serialized
     * files.
     *
     * @param string $font Font name (can contain both path and extension)
     *
     * @return void
     */
    protected function openFont($font) {
        // assume that $font contains both the path and perhaps the extension to the file, split them
        $pos = strrpos($font, '/');
        if ($pos === false) {
            // $dir  = './';
            $dir  = dirname(__FILE__) . '/fonts/';
            $name = $font;
        } else {
            $dir  = substr($font, 0, $pos + 1);
            $name = substr($font, $pos + 1);
        }

        //if (substr($name, -4) == '.afm' || substr($name, -4) == '.ufm') {
            //$name = substr($name, 0, strlen($name) - 4);
        //}
        
        if(!$this->isUnicode){
        	$metrics_name = "$name.afm";
        }else{
        	$metrics_name = "$name.ufm";
        }
        
        $this->debug('openFont executed: '.$font.' - '.$name.' / IsUnicode: '.$this->isUnicode);
        
        $cachedFile = 'cached'.$metrics_name.'.php';
        
        // use the temp folder to read/write cached font data
        if (file_exists($this->tempPath.'/'.$cachedFile)) {
            $this->debug('openFont: '.$this->tempPath.'/'.$cachedFile.' already exist');
            //$tmp = file($this->tempPath.'/'.$cachedFile);
            $this->fonts[$font] = require($this->tempPath.'/'.$cachedFile);
            if (!isset($this->fonts[$font]['_version_']) || $this->fonts[$font]['_version_']<2) {
                // if the font file is old, then clear it out and prepare for re-creation
                $this->debug('openFont: clear out, make way for new version.');
                unset($this->fonts[$font]);
            }
        }
        if (!isset($this->fonts[$font]) && file_exists($dir.$metrics_name)) {
            // then rebuild the php_<font>.afm file from the <font>.afm file
            $this->debug('openFont: (re)create '.$cachedFile);
            $data = array();
            // set unicode to true ufm file is used
            $data['isUnicode'] = (strtolower(substr($metrics_name, -3)) !== 'afm');
            
            $cidtogid = '';
      		if ($data['isUnicode']) {
        		$cidtogid = str_pad('', 256*256*2, "\x00");
      		}
            
            $file = file($dir.$metrics_name);
            foreach ($file as $row) {
                $row=trim($row);
                $pos=strpos($row,' ');
                if ($pos) {
                    // then there must be some keyword
                    $key = substr($row,0,$pos);
                    switch ($key) {
                    case 'FontName':
                    case 'FullName':
                    case 'FamilyName':
                    case 'Weight':
                    case 'ItalicAngle':
                    case 'IsFixedPitch':
                    case 'CharacterSet':
                    case 'UnderlinePosition':
                    case 'UnderlineThickness':
                    case 'Version':
                    case 'EncodingScheme':
                    case 'CapHeight':
                    case 'XHeight':
                    case 'Ascender':
                    case 'Descender':
                    case 'StdHW':
                    case 'StdVW':
                    case 'StartCharMetrics':
                        $data[$key]=trim(substr($row,$pos));
                        break;
                    case 'FontBBox':
                        $data[$key]=explode(' ',trim(substr($row,$pos)));
                        break;
                    case 'C':
                        // C 39 ; WX 222 ; N quoteright ; B 53 463 157 718 ;
                        // use preg_match instead to improve performace
                        // IMPORTANT: if "L i fi ; L l fl ;" is required preg_match must be amended
                        $r = preg_match('/C (-?\d+) ; WX (-?\d+) ; N (\w+) ; B (-?\d+) (-?\d+) (-?\d+) (-?\d+) ;/', $row, $m);
                        if($r == 1){
                        	//$dtmp = array('C'=> $m[1],'WX'=> $m[2], 'N' => $m[3], 'B' => array($m[4], $m[5], $m[6], $m[7]));
                        	$c = (int)$m[1];
            				$n = $m[3];
            				$width = floatval($m[2]);
            
                        	if($c >= 0){
                        		if ($c != hexdec($n)) {
                					$data['codeToName'][$c] = $n;
              					}
                        		$data['C'][$c] = $width;
                        		$data['C'][$n] = $width;
                        	}else{
                        		$data['C'][$n] = $width;
                        	}
                        	
                        	if (!isset($data['MissingWidth']) && $c == -1 && $n === '.notdef') {
			              		$data['MissingWidth'] = $width;
			            	}
                        }
                        break;
                    // U 827 ; WX 0 ; N squaresubnosp ; G 675 ;
			        case 'U': // Found in UFM files
			            if (!$data['isUnicode']) break;
			            
			            $r = preg_match('/U (-?\d+) ; WX (-?\d+) ; N (\w+) ; G (-?\d+) ;/', $row, $m);
			            
			            if($r == 1){
			            	//$dtmp = array('U'=> $m[1],'WX'=> $m[2], 'N' => $m[3], 'G' => $m[4]);
			            	$c = (int)$m[1];
			            	$n = $m[3];
			            	$glyph = $m[4];
			            	$width = floatval($m[2]);
			            	
			            	if($c >= 0){
			            		if ($c >= 0 && $c < 0xFFFF && $glyph) {
			               			$cidtogid[$c*2] = chr($glyph >> 8);
			                		$cidtogid[$c*2 + 1] = chr($glyph & 0xFF);
			              		}
			              		if ($c != hexdec($n)) {
                					$data['codeToName'][$c] = $n;
              					}
			            		$data['C'][$c] = $width;
			            	} else{
			            		$data['C'][$n] = $width;
			            	}
			            	
			            	if (!isset($data['MissingWidth']) && $c == -1 && $n === '.notdef') {
			              		$data['MissingWidth'] = $width;
			            	}
			            }
			            break;
                    case 'KPX':
                    	break;
                        // KPX Adieresis yacute -40
                        $bits=explode(' ',$row);
                        $data['KPX'][$bits[1]][$bits[2]]=$bits[3];
                        break;
                    }
                }
            }
            
            $data['CIDtoGID'] = base64_encode($cidtogid);
            $data['_version_']=2;
            
            $this->fonts[$font]=$data;
            $fp = fopen($this->tempPath.'/'.$cachedFile,'w'); // use the temp folder to write cached font data
            fwrite($fp,'<?php /* R&OS php pdf class font cache file */ return '.var_export($data,true).'; ?>');
            fclose($fp);
        } else if (!isset($this->fonts[$font])) {
            $this->debug(sprintf('openFont: no font file found for "'.$font.'" IsUnicode: %b', $font, $this->isUnicode), E_USER_WARNING);
        }
    }

    /**
     * if the font is not loaded then load it and make the required object
     * else just make it the current font
     * the encoding array can contain 'encoding'=> 'none','WinAnsiEncoding','MacRomanEncoding' or 'MacExpertEncoding'
     * note that encoding='none' will need to be used for symbolic fonts
     * and 'differences' => an array of mappings between numbers 0->255 and character names.
     *
     * @param string  $fontName Name of the font
     * @param string  $encoding Which encoding to use
     * @param integer $set      What is this
     *
     * @return void
     * @access public
     */
    public function selectFont($fontName, $encoding = '', $set = 1)
    {
    	$ext = substr($fontName, -4);
    	if ($ext === '.afm' || $ext === '.ufm') {
			$fontName = substr($fontName, 0, strlen($fontName)-4);
    	}
    	
    	$pos = strrpos($fontName, '/');
        if ($pos !== false) {
            $name = substr($fontName, $pos + 1);
        } else {
            $name = $fontName;
        }
        
        if (!isset($this->fonts[$fontName])){
            // load the file
            $this->openFont($fontName);
            if (isset($this->fonts[$fontName])){
                $this->numObj++;
                $this->numFonts++;
                
                $font = &$this->fonts[$fontName];
                $options = array('name' => $name, 'fontFileName' => $fontName);
                
                if (is_array($encoding)){
                    // then encoding and differences might be set
                    if (isset($encoding['encoding'])){
                        $options['encoding'] = $encoding['encoding'];
                    }
                    if (isset($encoding['differences'])){
                        $options['differences'] = $encoding['differences'];
                    }
                } else if (strlen($encoding)){
                    // then perhaps only the encoding has been set
                    $options['encoding'] = $encoding;
                }
                $fontObj = $this->numObj;
                $this->o_font($this->numObj, 'new', $options);
                $font['fontNum'] = $this->numFonts;
                // if this is a '.afm' font, and there is a '.pfa' file to go with it (as there
                // should be for all non-basic fonts), then load it into an object and put the
                // references into the font object
                
                $fbtype = '';
                if (file_exists($fontName.'.pfb')){
                    $fbtype = 'pfb';
                } else if (file_exists($fontName.'.ttf')){
                    $fbtype = 'ttf';
                }
                
                $fbfile = $fontName.'.'.$fbtype;
                
                if ($fbtype){
                    $adobeFontName = $font['FontName'];
                    // $fontObj = $this->numObj;
                    $this->debug('selectFont: adding font file "'.$fbfile.'" to pdf');
                    // find the array of fond widths, and put that into an object.
                    $firstChar = -1;
                    $lastChar = 0;
                    $widths = array();
                    $cid_widths = array();
                    
                    foreach ($font['C'] as $num => $d){
                        if (intval($num) > 0 || $num == '0'){
                        	if(!$font['isUnicode']){
	                            if ($lastChar > 0 && $num > $lastChar + 1){
	                                for($i = $lastChar + 1; $i < $num; $i++){
	                                    $widths[] = 0;
	                                }
	                            }
                            }
                            $widths[] = $d;
                            
                            if ($font['isUnicode']) {
                				$cid_widths[$num] = $d;
              				}
                            
                            if ($firstChar == -1){
                                $firstChar = $num;
                            }
                            $lastChar = $num;
                        }
                    }
                    // also need to adjust the widths for the differences array
                    if (isset($options['differences'])){
                        foreach ($options['differences'] as $charNum => $charName){
                            if ($charNum>$lastChar){
                                for($i = $lastChar + 1; $i <= $charNum; $i++) {
                                    $widths[]=0;
                                }
                                $lastChar = $charNum;
                            }
                            if (isset($font['C'][$charName])){
                                $widths[$charNum-$firstChar]=$font['C'][$charName];
                                if($font['isUnicode']){
                                	$cid_widths[$charName] = $font['C'][$charName];
                                }
                            }
                        }
                    }
                    
                    if($font['isUnicode']){
                    	$font['CIDWidths'] = $cid_widths;
                    }
                    $this->debug('selectFont: FirstChar='.$firstChar);
                    $this->debug('selectFont: LastChar='.$lastChar);
                    
                    $widthid = -1;
                    
                    if(!$font['isUnicode']){
                    	$this->numObj++;
                    	$this->o_contents($this->numObj, 'new', 'raw');
                    	$this->objects[$this->numObj]['c'].='['.implode(' ', $widths).']';
                    	$widthid = $this->numObj;
                    }
                    
                    $missing_width = 500;
                    $stemV = 70;
                    
                    if (isset($font['MissingWidth'])) {
            			$missing_width = $font['MissingWidth'];
          			}
          			if (isset($font['StdVW'])) {
            			$stemV = $font['StdVW'];
          			} else if (isset($font['Weight']) && preg_match('!(bold|black)!i', $font['Weight'])) {
            			$stemV = 120;
          			}

                    // load the pfb file, and put that into an object too.
                    // note that pdf supports only binary format type 1 font files, though there is a
                    // simple utility to convert them from pfa to pfb.
                    if($this->embedFont){
	                    if(!$this->isUnicode || $fbtype !== 'ttf'){
	                    	$data = file_get_contents($fbfile);
	                    }else{
	                    	$data = file_get_contents($fbfile);;
	                    }
                    }

                    // create the font descriptor
                    $this->numObj++;
                    $fontDescriptorId = $this->numObj;
                    
                    $this->numObj++;
                    $pfbid = $this->numObj;
                    // determine flags (more than a little flakey, hopefully will not matter much)
                    $flags=0;
                    if ($font['ItalicAngle']!=0){
                    	$flags+=pow(2,6);
                    }
                    if ($font['IsFixedPitch']=='true'){
                    	$flags+=1;
                    }
                    $flags+=pow(2,5); // assume non-sybolic

                    $list = array('Ascent'=>'Ascender','CapHeight'=>'CapHeight','Descent'=>'Descender','FontBBox'=>'FontBBox','ItalicAngle'=>'ItalicAngle');
                    $fdopt = array(
                        'Flags' => $flags,
                        'FontName' => $adobeFontName,
                        'StemV' => $stemV
                    );
                    foreach($list as $k=>$v){
                        if (isset($font[$v])){
                            $fdopt[$k]=$font[$v];
                        }
                    }

					if($this->embedFont){
	                    if ($fbtype=='pfb'){
	                        $fdopt['FontFile']=$pfbid;
	                    } else if ($fbtype=='ttf' && $this->embedFont){
	                        $fdopt['FontFile2']=$pfbid;
	                    }
                    }
                    
                    $this->o_fontDescriptor($fontDescriptorId,'new',$fdopt);

                    // embed the font program
                    if($this->embedFont){
	                    $this->o_contents($this->numObj,'new');
	                    $this->objects[$pfbid]['c'].= $data;
	                    // determine the cruicial lengths within this file
	                    if ($fbtype=='pfb'){
	                        $l1 = strpos($data,'eexec')+6;
	                        $l2 = strpos($data,'00000000')-$l1;
	                        $l3 = strlen($data)-$l2-$l1;
	                        $this->o_contents($this->numObj,'add',array('Length1'=>$l1,'Length2'=>$l2,'Length3'=>$l3));
	                    } else if ($fbtype=='ttf'){
	                    	$l1 = strlen($data);
	                    	$this->o_contents($this->numObj,'add',array('Length1'=>$l1));
	                    }
	                }

                    // tell the font object about all this new stuff
                    $tmp = array('BaseFont'=>$adobeFontName,'Widths'=>$widthid
                                      ,'FirstChar'=>$firstChar,'LastChar'=>$lastChar
                                      ,'FontDescriptor'=>$fontDescriptorId);
                    if ($fbtype=='ttf'){
                        $tmp['SubType']='TrueType';
                    }
                    $this->debug('selectFont: adding extra info to font.('.$fontObj.')');
                    foreach($tmp as $fk=>$fv){
                        $this->debug($fk." : ".$fv);
                    }
                    $this->o_font($fontObj,'add',$tmp);

                } else if(!in_array(strtolower($name), $this->coreFonts)) {
                    $this->debug('selectFont: No pfb/ttf file found for "'.$name.'"', E_USER_WARNING);
                }


                // also set the differences here, note that this means that these will take effect only the
                // first time that a font is selected, else they are ignored
                if (isset($options['differences'])){
                    $font['differences']=$options['differences'];
                }
            }
        }
        
        if ($set && isset($this->fonts[$fontName])){
            // so if for some reason the font was not set in the last one then it will not be selected
            $this->currentBaseFont=$fontName;
            // the next line means that if a new font is selected, then the current text state will be
            // applied to it as well.
            $this->setCurrentFont();
        }
        return $this->currentFontNum;
    }

    /**
     * sets up the current font, based on the font families, and the current text state
     * note that this system is quite flexible, a <b><i> font can be completely different to a
     * <i><b> font, and even <b><b> will have to be defined within the family to have meaning
     * This function is to be called whenever the currentTextState is changed, it will update
     * the currentFont setting to whatever the appropriatte family one is.
     * If the user calls selectFont themselves then that will reset the currentBaseFont, and the currentFont
     * This function will change the currentFont to whatever it should be, but will not change the
     * currentBaseFont.
     *
     * @access protected
     */
    protected function setCurrentFont(){
        if (strlen($this->currentBaseFont)==0){
            // then assume an initial font
            $this->selectFont(dirname(__FILE__) . '/fonts/Helvetica');
        }
        $pos = strrpos($this->currentBaseFont, '/');
        if ($pos !== false) {
            $cf = substr($this->currentBaseFont, $pos + 1);
        } else {
            $cf = $this->currentBaseFont;
        }
        if (strlen($this->currentTextState)
            && isset($this->fontFamilies[$cf])
            && isset($this->fontFamilies[$cf][$this->currentTextState])){
            // then we are in some state or another
            // and this font has a family, and the current setting exists within it
            // select the font, then return it
            if ($pos !== false) {
                $nf = substr($this->currentBaseFont, 0, strrpos($this->currentBaseFont,'/') + 1).$this->fontFamilies[$cf][$this->currentTextState];
            } else {
                $nf = $this->fontFamilies[$cf][$this->currentTextState];
            }
            $this->selectFont($nf,'',0);
            $this->currentFont = $nf;
            $this->currentFontNum = $this->fonts[$nf]['fontNum'];
        } else {
            // the this font must not have the right family member for the current state
            // simply assume the base font
            $this->currentFont = $this->currentBaseFont;
            $this->currentFontNum = $this->fonts[$this->currentFont]['fontNum'];
        }
    }

    /**
     * function for the user to find out what the ID is of the first page that was created during
     * startup - useful if they wish to add something to it later.
     * @access protected
     */
    protected function getFirstPageId(){
        return $this->firstPageId;
    }

    /**
     * add content to the currently active object
     * @access protected
     */
    protected function addContent($content){
        $this->objects[$this->currentContents]['c'].=$content;
    }

    /**
     * sets the colour for fill operations
     * @access public
     */
    public function setColor($r,$g,$b,$force=0){
        if ($r>=0 && ($force || $r!=$this->currentColour['r'] || $g!=$this->currentColour['g'] || $b!=$this->currentColour['b'])){
            $this->objects[$this->currentContents]['c'].="\n".sprintf('%.3F',$r).' '.sprintf('%.3F',$g).' '.sprintf('%.3F',$b).' rg';
            $this->currentColour=array('r'=>$r,'g'=>$g,'b'=>$b);
        }
    }

    /**
     * sets the colour for stroke operations
     * @access public
     */
    public function setStrokeColor($r,$g,$b,$force=0){
        if ($r>=0 && ($force || $r!=$this->currentStrokeColour['r'] || $g!=$this->currentStrokeColour['g'] || $b!=$this->currentStrokeColour['b'])){
            $this->objects[$this->currentContents]['c'].="\n".sprintf('%.3F',$r).' '.sprintf('%.3F',$g).' '.sprintf('%.3F',$b).' RG';
            $this->currentStrokeColour=array('r'=>$r,'g'=>$g,'b'=>$b);
        }
    }

    /**
     * draw a line from one set of coordinates to another
     * @access public
     */
    public function line($x1,$y1,$x2,$y2){
        $this->objects[$this->currentContents]['c'].="\n".sprintf('%.3F',$x1).' '.sprintf('%.3F',$y1).' m '.sprintf('%.3F',$x2).' '.sprintf('%.3F',$y2).' l S';
    }

    /**
     * draw a bezier curve based on 4 control points
     * @access public
     */
    public function curve($x0,$y0,$x1,$y1,$x2,$y2,$x3,$y3){
        // in the current line style, draw a bezier curve from (x0,y0) to (x3,y3) using the other two points
        // as the control points for the curve.
        $this->objects[$this->currentContents]['c'].="\n".sprintf('%.3F',$x0).' '.sprintf('%.3F',$y0).' m '.sprintf('%.3F',$x1).' '.sprintf('%.3F',$y1);
        $this->objects[$this->currentContents]['c'].= ' '.sprintf('%.3F',$x2).' '.sprintf('%.3F',$y2).' '.sprintf('%.3F',$x3).' '.sprintf('%.3F',$y3).' c S';
    }

    /**
     * draw a part of an ellipse
     * @access public
     */
    public function partEllipse($x0,$y0,$astart,$afinish,$r1,$r2=0,$angle=0,$nSeg=8){
        $this->ellipse($x0,$y0,$r1,$r2,$angle,$nSeg,$astart,$afinish,0);
    }

    /**
     * draw a filled ellipse
     * @access public
     */
    public function filledEllipse($x0,$y0,$r1,$r2=0,$angle=0,$nSeg=8,$astart=0,$afinish=360){
        return $this->ellipse($x0,$y0,$r1,$r2=0,$angle,$nSeg,$astart,$afinish,1,1);
    }

    /**
     * draw an ellipse
     * note that the part and filled ellipse are just special cases of this function
     *
     * draws an ellipse in the current line style
     * centered at $x0,$y0, radii $r1,$r2
     * if $r2 is not set, then a circle is drawn
     * nSeg is not allowed to be less than 2, as this will simply draw a line (and will even draw a
     * pretty crappy shape at 2, as we are approximating with bezier curves.
     * @access public
     */
    public function ellipse($x0,$y0,$r1,$r2=0,$angle=0,$nSeg=8,$astart=0,$afinish=360,$close=1,$fill=0){
        if ($r1==0){
            return;
        }
        if ($r2==0){
            $r2=$r1;
        }
        if ($nSeg<2){
            $nSeg=2;
        }

        $astart = deg2rad((float)$astart);
        $afinish = deg2rad((float)$afinish);
        $totalAngle =$afinish-$astart;

        $dt = $totalAngle/$nSeg;
        $dtm = $dt/3;

        if ($angle != 0){
            $a = -1*deg2rad((float)$angle);
            $tmp = "\n q ";
            $tmp .= sprintf('%.3F',cos($a)).' '.sprintf('%.3F',(-1.0*sin($a))).' '.sprintf('%.3F',sin($a)).' '.sprintf('%.3F',cos($a)).' ';
            $tmp .= sprintf('%.3F',$x0).' '.sprintf('%.3F',$y0).' cm';
            $this->objects[$this->currentContents]['c'].= $tmp;
            $x0=0;
            $y0=0;
        }

        $t1 = $astart;
        $a0 = $x0+$r1*cos($t1);
        $b0 = $y0+$r2*sin($t1);
        $c0 = -$r1*sin($t1);
        $d0 = $r2*cos($t1);

        $this->objects[$this->currentContents]['c'].="\n".sprintf('%.3F',$a0).' '.sprintf('%.3F',$b0).' m ';
        for ($i=1;$i<=$nSeg;$i++){
            // draw this bit of the total curve
            $t1 = $i*$dt+$astart;
            $a1 = $x0+$r1*cos($t1);
            $b1 = $y0+$r2*sin($t1);
            $c1 = -$r1*sin($t1);
            $d1 = $r2*cos($t1);
            $this->objects[$this->currentContents]['c'].="\n".sprintf('%.3F',($a0+$c0*$dtm)).' '.sprintf('%.3F',($b0+$d0*$dtm));
            $this->objects[$this->currentContents]['c'].= ' '.sprintf('%.3F',($a1-$c1*$dtm)).' '.sprintf('%.3F',($b1-$d1*$dtm)).' '.sprintf('%.3F',$a1).' '.sprintf('%.3F',$b1).' c';
            $a0=$a1;
            $b0=$b1;
            $c0=$c1;
            $d0=$d1;
        }
        if ($fill){
            $this->objects[$this->currentContents]['c'].=' f';
        } else {
            if ($close){
                $this->objects[$this->currentContents]['c'].=' s'; // small 's' signifies closing the path as well
            } else {
                $this->objects[$this->currentContents]['c'].=' S';
            }
        }
        if ($angle !=0){
            $this->objects[$this->currentContents]['c'].=' Q';
        }
    }

    /**
     * this sets the line drawing style.
     * width, is the thickness of the line in user units
     * cap is the type of cap to put on the line, values can be 'butt','round','square'
     *    where the diffference between 'square' and 'butt' is that 'square' projects a flat end past the
     *    end of the line.
     * join can be 'miter', 'round', 'bevel'
     * dash is an array which sets the dash pattern, is a series of length values, which are the lengths of the
     *   on and off dashes.
     *   (2) represents 2 on, 2 off, 2 on , 2 off ...
     *   (2,1) is 2 on, 1 off, 2 on, 1 off.. etc
     * phase is a modifier on the dash pattern which is used to shift the point at which the pattern starts.
     * @access public
     */
    public function setLineStyle($width=1,$cap='',$join='',$dash='',$phase=0){

        // this is quite inefficient in that it sets all the parameters whenever 1 is changed, but will fix another day
        $string = '';
        if ($width>0){
            $string.= $width.' w';
        }
        $ca = array('butt'=>0,'round'=>1,'square'=>2);
        if (isset($ca[$cap])){
            $string.= ' '.$ca[$cap].' J';
        }
        $ja = array('miter'=>0,'round'=>1,'bevel'=>2);
        if (isset($ja[$join])){
            $string.= ' '.$ja[$join].' j';
        }
        if (is_array($dash)){
            $string.= ' [';
            foreach ($dash as $len){
                $string.=' '.$len;
            }
            $string.= ' ] '.$phase.' d';
        }
        $this->currentLineStyle = $string;
        $this->objects[$this->currentContents]['c'].="\n".$string;
    }

    /**
     * draw a polygon, the syntax for this is similar to the GD polygon command
     * @access public
     */
    public function polygon($p,$np,$f=0){
        $this->objects[$this->currentContents]['c'].="\n";
        $this->objects[$this->currentContents]['c'].=sprintf('%.3F',$p[0]).' '.sprintf('%.3F',$p[1]).' m ';
        for ($i=2;$i<$np*2;$i=$i+2){
            $this->objects[$this->currentContents]['c'].= sprintf('%.3F',$p[$i]).' '.sprintf('%.3F',$p[$i+1]).' l ';
        }
        if ($f==1){
            $this->objects[$this->currentContents]['c'].=' f';
        } else {
            $this->objects[$this->currentContents]['c'].=' S';
        }
    }

    /**
     * a filled rectangle, note that it is the width and height of the rectangle which are the secondary paramaters, not
     * the coordinates of the upper-right corner
     * @access public
     */
    public function filledRectangle($x1,$y1,$width,$height){
        $this->objects[$this->currentContents]['c'].="\n".sprintf('%.3F',$x1).' '.sprintf('%.3F',$y1).' '.sprintf('%.3F',$width).' '.sprintf('%.3F',$height).' re f';
    }

    /**
     * draw a rectangle, note that it is the width and height of the rectangle which are the secondary paramaters, not
     * the coordinates of the upper-right corner
     * @access public 
     */
    public function rectangle($x1,$y1,$width,$height){
        $this->objects[$this->currentContents]['c'].="\n".sprintf('%.3F',$x1).' '.sprintf('%.3F',$y1).' '.sprintf('%.3F',$width).' '.sprintf('%.3F',$height).' re S';
    }

    /**
     * add a new page to the document
     * this also makes the new page the current active object
     * @access public
     */
    public function newPage($insert=0,$id=0,$pos='after'){

        // if there is a state saved, then go up the stack closing them
        // then on the new page, re-open them with the right setings

        if ($this->nStateStack){
            for ($i=$this->nStateStack;$i>=1;$i--){
                $this->restoreState($i);
            }
        }

        $this->numObj++;
        if ($insert){
            // the id from the ezPdf class is the od of the contents of the page, not the page object itself
            // query that object to find the parent
            $rid = $this->objects[$id]['onPage'];
            $opt= array('rid'=>$rid,'pos'=>$pos);
            $this->o_page($this->numObj,'new',$opt);
        } else {
            $this->o_page($this->numObj,'new');
        }
        // if there is a stack saved, then put that onto the page
        if ($this->nStateStack){
            for ($i=1;$i<=$this->nStateStack;$i++){
                $this->saveState($i);
            }
        }
        // and if there has been a stroke or fill colour set, then transfer them
        if ($this->currentColour['r']>=0){
            $this->setColor($this->currentColour['r'],$this->currentColour['g'],$this->currentColour['b'],1);
        }
        if ($this->currentStrokeColour['r']>=0){
            $this->setStrokeColor($this->currentStrokeColour['r'],$this->currentStrokeColour['g'],$this->currentStrokeColour['b'],1);
        }

        // if there is a line style set, then put this in too
        if (strlen($this->currentLineStyle)){
            $this->objects[$this->currentContents]['c'].="\n".$this->currentLineStyle;
        }

        // the call to the o_page object set currentContents to the present page, so this can be returned as the page id
        return $this->currentContents;
    }

    /**
     * output the pdf code, streaming it to the browser
     * the relevant headers are set so that hopefully the browser will recognise it
     * this method is protected to force user to use ezStream method from Cezpdf.php
     * @access protected
     */
    protected function stream($options=''){
        // setting the options allows the adjustment of the headers
        // values at the moment are:
        // 'Content-Disposition'=>'filename'  - sets the filename, though not too sure how well this will
        //        work as in my trial the browser seems to use the filename of the php file with .pdf on the end
        // 'Accept-Ranges'=>1 or 0 - if this is not set to 1, then this header is not included, off by default
        //    this header seems to have caused some problems despite tha fact that it is supposed to solve
        //    them, so I am leaving it off by default.
        // 'compress'=> 1 or 0 - apply content stream compression, this is on (1) by default
        // 'download'=> 1 or 0 - provide download dialog
        if (!is_array($options)){
            $options=array();
        }
        if ( isset($options['compress']) && $options['compress']==0){
            $tmp = $this->output(1);
        } else {
            $tmp = $this->output();
        }
        header("Content-type: application/pdf");
        header("Content-Length: ".strlen(ltrim($tmp)));
        $fileName = (isset($options['Content-Disposition'])?$options['Content-Disposition']:'file.pdf');
        if(isset($options['download']) && $options['download'] == 1)
        	$attached = 'attachment';
        else
        	$attached = 'inline';
        header("Content-Disposition: $attached; filename=".$fileName);
        if (isset($options['Accept-Ranges']) && $options['Accept-Ranges']==1){
            header("Accept-Ranges: ".strlen(ltrim($tmp)));
        }
        echo ltrim($tmp);
    }

    /**
     * return the height in units of the current font in the given size
     * @access public
     */
    public function getFontHeight($size){
        if (!$this->numFonts){
            $this->selectFont('./fonts/Helvetica');
        }
        
        $font = &$this->fonts[$this->currentFont];
        // for the current font, and the given size, what is the height of the font in user units
        $h = $font['FontBBox'][3] - $font['FontBBox'][1];
    	return $size*$h/1000;
    }

    /**
     * return the font decender, this will normally return a negative number
     * if you add this number to the baseline, you get the level of the bottom of the font
     * it is in the pdf user units
     * @access public
     */
    public function getFontDecender($size){
        // note that this will most likely return a negative value
        if (!$this->numFonts){
            $this->selectFont('./fonts/Helvetica');
        }
        $h = $this->fonts[$this->currentFont]['Descender'];
        return $size*$h/1000;
    }

    /**
     * filter the text, this is applied to all text just before being inserted into the pdf document
     * it escapes the various things that need to be escaped, and so on
     *
     * @access protected
     */
    protected function filterText($text, $bom = true, $convert_encoding = true){
    	
    	if ($convert_encoding) {
	      $cf = $this->currentFont;
	      if (isset($this->fonts[$cf]) && $this->fonts[$cf]['isUnicode']) {
	        //$text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
	        $text = $this->utf8toUtf16BE($text, $bom);
	      } else {
	        //$text = html_entity_decode($text, ENT_QUOTES);
	        $text = mb_convert_encoding($text, $this->targetEncoding, 'UTF-8');
	      }
	    }
    	
    	$text = strtr($text,  array(')' => '\\)', '(' => '\\(', '\\' => '\\\\', chr(8) => '\\b', chr(9) => '\\t', chr(10) => '\\n', chr(12) => '\\f' ,chr(13) => '\\r') );

		if($this->rtl){
			$text = strrev($text);
		}

        return $text;
    }

	/**
	   * return array containing codepoints (UTF-8 character values) for the
	   * string passed in.
	   *
	   * based on the excellent TCPDF code by Nicola Asuni and the
	   * RFC for UTF-8 at http://www.faqs.org/rfcs/rfc3629.html
	   *
	   * @access private
	   * @author Orion Richardson
	   * @since January 5, 2008
	   * @param string $text UTF-8 string to process
	   * @return array UTF-8 codepoints array for the string
	   */
	  private function utf8toCodePointsArray(&$text) {
	    $length = mb_strlen($text, '8bit'); // http://www.php.net/manual/en/function.mb-strlen.php#77040
	    $unicode = array(); // array containing unicode values
	    $bytes = array(); // array containing single character byte sequences
	    $numbytes = 1; // number of octetc needed to represent the UTF-8 character

	    for ($i = 0; $i < $length; $i++) {
	      $c = ord($text[$i]); // get one string character at time
	      if (count($bytes) === 0) { // get starting octect
	        if ($c <= 0x7F) {
	          $unicode[] = $c; // use the character "as is" because is ASCII
	          $numbytes = 1;
	        } elseif (($c >> 0x05) === 0x06) { // 2 bytes character (0x06 = 110 BIN)
	          $bytes[] = ($c - 0xC0) << 0x06;
	          $numbytes = 2;
	        } elseif (($c >> 0x04) === 0x0E) { // 3 bytes character (0x0E = 1110 BIN)
	          $bytes[] = ($c - 0xE0) << 0x0C;
	          $numbytes = 3;
	        } elseif (($c >> 0x03) === 0x1E) { // 4 bytes character (0x1E = 11110 BIN)
	          $bytes[] = ($c - 0xF0) << 0x12;
	          $numbytes = 4;
	        } else {
	          // use replacement character for other invalid sequences
	          $unicode[] = 0xFFFD;
	          $bytes = array();
	          $numbytes = 1;
	        }
	      } elseif (($c >> 0x06) === 0x02) { // bytes 2, 3 and 4 must start with 0x02 = 10 BIN
	        $bytes[] = $c - 0x80;
	        if (count($bytes) === $numbytes) {
	          // compose UTF-8 bytes to a single unicode value
	          $c = $bytes[0];
	          for ($j = 1; $j < $numbytes; $j++) {
	            $c += ($bytes[$j] << (($numbytes - $j - 1) * 0x06));
	          }
	          if ((($c >= 0xD800) AND ($c <= 0xDFFF)) OR ($c >= 0x10FFFF)) {
	            // The definition of UTF-8 prohibits encoding character numbers between
	            // U+D800 and U+DFFF, which are reserved for use with the UTF-16
	            // encoding form (as surrogate pairs) and do not directly represent
	            // characters.
	            $unicode[] = 0xFFFD; // use replacement character
	          } else {
	            $unicode[] = $c; // add char to array
	          }
	          // reset data for next char
	          $bytes = array();
	          $numbytes = 1;
	        }
	      } else {
	        // use replacement character for other invalid sequences
	        $unicode[] = 0xFFFD;
	        $bytes = array();
	        $numbytes = 1;
	      }
	    }
	    return $unicode;
	  }

	  /**
	   * convert UTF-8 to UTF-16 with an additional byte order marker
	   * at the front if required.
	   *
	   * based on the excellent TCPDF code by Nicola Asuni and the
	   * RFC for UTF-8 at http://www.faqs.org/rfcs/rfc3629.html
	   *
	   * @access private
	   * @author Orion Richardson
	   * @since January 5, 2008
	   * @param string $text UTF-8 string to process
	   * @param boolean $bom whether to add the byte order marker
	   * @return string UTF-16 result string
	   */
	  private function utf8toUtf16BE(&$text, $bom = true) {
	    $cf = $this->currentFont;
	    if (!$this->fonts[$cf]['isUnicode']) return $text;
	    $out = $bom ? "\xFE\xFF" : '';
	    $unicode = $this->utf8toCodePointsArray($text);
	    foreach ($unicode as $c) {
	      if ($c === 0xFFFD) {
	        $out .= "\xFF\xFD"; // replacement character
	      } elseif ($c < 0x10000) {
	        $out .= chr($c >> 0x08) . chr($c & 0xFF);
	       } else {
	        $c -= 0x10000;
	        $w1 = 0xD800 | ($c >> 0x10);
	        $w2 = 0xDC00 | ($c & 0x3FF);
	        $out .= chr($w1 >> 0x08) . chr($w1 & 0xFF) . chr($w2 >> 0x08) . chr($w2 & 0xFF);
	      }
	    }
	    return $out;
	  }

    /**
     * given a start position and information about how text is to be laid out, calculate where
     * on the page the text will end
     *
     * @access protected
     */
    protected function getTextPosition($x,$y,$angle,$size,$wa,$text){
        // given this information return an array containing x and y for the end position as elements 0 and 1
        $w = $this->getTextWidth($size,$text);
        // need to adjust for the number of spaces in this text
        $words = explode(' ',$text);
        $nspaces=count($words)-1;
        $w += $wa*$nspaces;
        $a = deg2rad((float)$angle);
        return array(cos($a)*$w+$x,-sin($a)*$w+$y);
    }

    /**
     * wrapper function for checkTextDirective1
     *
     * @access private
     */
    private function checkTextDirective(&$text,$i,&$f){
        $x=0;
        $y=0;
        return $this->checkTextDirective1($text,$i,$f,0,$x,$y);
    }

    /**
     * checks if the text stream contains a control directive
     * if so then makes some changes and returns the number of characters involved in the directive
     * this has been re-worked to include everything neccesary to fins the current writing point, so that
     * the location can be sent to the callback function if required
     * if the directive does not require a font change, then $f should be set to 0
     *
     * @access private
     */
    private function checkTextDirective1(&$text,$i,&$f,$final,&$x,&$y,$size=0,$angle=0,$wordSpaceAdjust=0){
        $directive = 0;
        $j=$i;
        if ($text[$j]=='<'){
            $j++;
            switch($text[$j]){
            case '/':
                $j++;
                if (strlen($text) <= $j){
                    return $directive;
                }
                switch($text[$j]){
                case 'b':
                case 'i':
                    $j++;
                    if ($text[$j]=='>'){
                        $p = strrpos($this->currentTextState,$text[$j-1]);
                        if ($p !== false){
                            // then there is one to remove
                            $this->currentTextState = substr($this->currentTextState,0,$p).substr($this->currentTextState,$p+1);
                        }
                        $directive=$j-$i+1;
                    }
                    break;
                case 'c':
                    // this this might be a callback function
                    $j++;
                    $k = strpos($text,'>',$j);
                    if ($k!==false && $text[$j]==':'){
                        // then this will be treated as a callback directive
                        $directive = $k-$i+1;
                        $f=0;
                        // split the remainder on colons to get the function name and the paramater
                        $tmp = substr($text,$j+1,$k-$j-1);
                        $b1 = strpos($tmp,':');
                        if ($b1!==false){
                            $func = substr($tmp,0,$b1);
                            $parm = substr($tmp,$b1+1);
                        } else {
                            $func=$tmp;
                            $parm='';
                        }
                        if (!isset($func) || !strlen(trim($func))){
                            $directive=0;
                        } else {
                            // only call the function if this is the final call
                            if ($final){
                                // need to assess the text position, calculate the text width to this point
                                // can use getTextWidth to find the text width I think
                                $tmp = $this->getTextPosition($x,$y,$angle,$size,$wordSpaceAdjust,substr($text,0,$i));
                                $info = array('x'=>$tmp[0],'y'=>$tmp[1],'angle'=>$angle,'status'=>'end','p'=>$parm,'nCallback'=>$this->nCallback);
                                $x=$tmp[0];
                                $y=$tmp[1];
                                $ret = $this->$func($info);
                                if (is_array($ret)){
                                    // then the return from the callback function could set the position, to start with, later will do font colour, and font
                                    foreach($ret as $rk=>$rv){
                                        switch($rk){
                                        case 'x':
                                        case 'y':
                                            $$rk=$rv;
                                            break;
                                        }
                                    }
                                }
                                // also remove from to the stack
                                // for simplicity, just take from the end, fix this another day
                                $this->nCallback--;
                                if ($this->nCallback<0){
                                    $this->nCallBack=0;
                                }
                            }
                        }
                    }
                    break;
                }
                break;
            case 'b':
            case 'i':
                $j++;
                if ($text[$j]=='>'){
                    $this->currentTextState.=$text[$j-1];
                    $directive=$j-$i+1;
                }
                break;
            case 'C':
                $noClose=1;
            case 'c':
                // this this might be a callback function
                $j++;
                $k = strpos($text,'>',$j);
                if ($k!==false && $text[$j]==':'){
                    // then this will be treated as a callback directive
                    $directive = $k-$i+1;
                    $f=0;
                    // split the remainder on colons to get the function name and the paramater
                    // $bits = explode(':',substr($text,$j+1,$k-$j-1));
                    $tmp = substr($text,$j+1,$k-$j-1);
                    $b1 = strpos($tmp,':');
                    if ($b1!==false){
                        $func = substr($tmp,0,$b1);
                        $parm = substr($tmp,$b1+1);
                    } else {
                        $func=$tmp;
                        $parm='';
                    }
                    if (!isset($func) || !strlen(trim($func))){
                        $directive=0;
                    } else {
                    // only call the function if this is the final call, ie, the one actually doing printing, not measurement
                    if ($final){
                        // need to assess the text position, calculate the text width to this point
                        // can use getTextWidth to find the text width I think
                        // also add the text height and decender
                        $tmp = $this->getTextPosition($x,$y,$angle,$size,$wordSpaceAdjust,substr($text,0,$i));
                        $info = array('x'=>$tmp[0],'y'=>$tmp[1],'angle'=>$angle,'status'=>'start','p'=>$parm,'f'=>$func,'height'=>$this->getFontHeight($size),'decender'=>$this->getFontDecender($size));
                        $x=$tmp[0];
                        $y=$tmp[1];
                        if (!isset($noClose) || !$noClose){
                            // only add to the stack if this is a small 'c', therefore is a start-stop pair
                            $this->nCallback++;
                            $info['nCallback']=$this->nCallback;
                            $this->callback[$this->nCallback]=$info;
                        }
                        $ret = $this->$func($info);
                        if (is_array($ret)){
                            // then the return from the callback function could set the position, to start with, later will do font colour, and font
                            foreach($ret as $rk=>$rv){
                                switch($rk){
                                case 'x':
                                case 'y':
                                    $$rk=$rv;
                                break;
                                }
                            }
                        }
                        }
                    }
                }
                break;
            }
        }
        return $directive;
    }

    /**
     * add text to the document, at a specified location, size and angle on the page
     * @access public
     */
    public function addText($x, $y, $size, $text, $angle = 0, $wordSpaceAdjust = 0) {
        if (!$this->numFonts) {
            $this->selectFont(dirname(__FILE__) . '/fonts/Helvetica');
        }

        // if there are any open callbacks, then they should be called, to show the start of the line
        if ($this->nCallback > 0){
            for ($i = $this->nCallback; $i > 0; $i--){
                // call each function
                $info = array('x'=>$x,'y'=>$y,'angle'=>$angle,'status'=>'sol','p'=>$this->callback[$i]['p'],'nCallback'=>$this->callback[$i]['nCallback'],'height'=>$this->callback[$i]['height'],'decender'=>$this->callback[$i]['decender']);
                $func = $this->callback[$i]['f'];
                $this->$func($info);
            }
        }
        if ($angle == 0) {
	      $this->addContent(sprintf("\nBT %.3F %.3F Td", $x, $y));
	    } else {
	      $a = deg2rad((float)$angle);
	      $this->addContent(sprintf("\nBT %.3F %.3F %.3F %.3F %.3F %.3F Tm", cos($a), -sin($a), sin($a), cos($a), $x, $y));
	    }

	    if ($wordSpaceAdjust != 0 || $wordSpaceAdjust != $this->wordSpaceAdjust) {
	      $this->wordSpaceAdjust = $wordSpaceAdjust;
	      $this->addContent(sprintf(" %.3F Tw", $wordSpaceAdjust));
	    }

	    $len = strlen($text);
        $start=0;
        for ($i=0;$i<$len;$i++){
            $f=1;
            $directive = $this->checkTextDirective($text,$i,$f);
            if ($directive){
                // then we should write what we need to
                if ($i>$start){
                    $part = substr($text,$start,$i-$start);
                    $this->addContent(' /F'.$this->currentFontNum.' '.sprintf('%.1f',$size).' Tf ');
                    $this->addContent(' ('.$this->filterText($part, false).') Tj');
                }
                if ($f){
                    // then there was nothing drastic done here, restore the contents
                    $this->setCurrentFont();
                } else {
                    $this->addContent(' ET');
                    $f=1;
                    $xp=$x;
                    $yp=$y;
                    $directive = $this->checkTextDirective1($text,$i,$f,1,$xp,$yp,$size,$angle,$wordSpaceAdjust);

                    // restart the text object
                    if ($angle==0){
                        $this->addContent("\n".'BT '.sprintf('%.3F',$xp).' '.sprintf('%.3F',$yp).' Td');
                    } else {
                        $a = deg2rad((float)$angle);
                        $tmp = "\n".'BT ';
                        $tmp .= sprintf('%.3F',cos($a)).' '.sprintf('%.3F',(-1.0*sin($a))).' '.sprintf('%.3F',sin($a)).' '.sprintf('%.3F',cos($a)).' ';
                        $tmp .= sprintf('%.3F',$xp).' '.sprintf('%.3F',$yp).' Tm';
                        $this->addContent($tmp);
                    }
                    if ($wordSpaceAdjust!=0 || $wordSpaceAdjust != $this->wordSpaceAdjust){
                        $this->wordSpaceAdjust=$wordSpaceAdjust;
                        $this->addContent(' '.sprintf('%.3F',$wordSpaceAdjust).' Tw');
                    }
                }
                // and move the writing point to the next piece of text
                $i=$i+$directive-1;
                $start=$i+1;
            }
        }

	    if ($start < $len) {
	      $part = substr($text,$start);
	      $place_text = $this->filterText($part, false);
	      // modify unicode text so that extra word spacing is manually implemented (bug #)
	      $cf = $this->currentFont;
	      if ($this->fonts[$cf]['isUnicode'] && $wordSpaceAdjust != 0) {
	        $space_scale = 1000 / $size;
	        $place_text = str_replace(' ', ' ) '.(-round($space_scale*$wordSpaceAdjust)).' (', $place_text);
	      }
	      $this->addContent(" /F$this->currentFontNum ".sprintf('%.1F Tf ', $size));
	      $this->addContent(" [($place_text)] TJ");
	    }

	    $this->addContent(' ET');

	    // if there are any open callbacks, then they should be called, to show the end of the line
	    if ($this->nCallback > 0) {
	      for ($i = $this->nCallback; $i > 0; $i--) {
	        // call each function
	        $tmp = $this->getTextPosition($x, $y, $angle, $size, $wordSpaceAdjust, $text);
	        $info = array(
	          'x' => $tmp[0],
	          'y' => $tmp[1],
	          'angle' => $angle,
	          'status' => 'eol',
	          'p'         => $this->callback[$i]['p'],
	          'nCallback' => $this->callback[$i]['nCallback'],
	          'height'    => $this->callback[$i]['height'],
	          'descender' => $this->callback[$i]['descender']
	        );
	        $func = $this->callback[$i]['f'];
	        $this->$func($info);
	      }
	    }
    }

    /**
     * calculate how wide a given text string will be on a page, at a given size.
     * this can be called externally, but is alse used by the other class functions
     * @access public
     */
    public function getTextWidth($size,$text){
        // this function should not change any of the settings, though it will need to
        // track any directives which change during calculation, so copy them at the start
        // and put them back at the end.
        $store_currentTextState = $this->currentTextState;

        if (!$this->numFonts){
            $this->selectFont('./fonts/Helvetica');
        }

        // converts a number or a float to a string so it can get the width
        $text = "$text";

        // hmm, this is where it all starts to get tricky - use the font information to
        // calculate the width of each character, add them up and convert to user units
        $w=0;
        $len=strlen($text);
        $cf = $this->currentFont;
        for ($i=0;$i<$len;$i++){
            $f=1;
            $directive = $this->checkTextDirective($text,$i,$f);
            if ($directive){
                if ($f){
                    $this->setCurrentFont();
                    $cf = $this->currentFont;
                }
                $i=$i+$directive-1;
            } else {
                $char=ord($text[$i]);
                if (isset($this->fonts[$cf]['differences'][$char])){
                    // then this character is being replaced by another
                    $name = $this->fonts[$cf]['differences'][$char];
                    if (isset($this->fonts[$cf]['C'][$name])){
                        $w+=$this->fonts[$cf]['C'][$name];
                    }
                } else if (isset($this->fonts[$cf]['C'][$char])){
                    $w+=$this->fonts[$cf]['C'][$char];
                }
            }
        }

        $this->currentTextState = $store_currentTextState;
        $this->setCurrentFont();

        return $w*$size/1000;
    }

    /**
     * do a part of the calculation for sorting out the justification of the text
     *
     * @access private
     */
    private function adjustWrapText($text,$actual,$width,&$x,&$adjust,$justification){
        switch ($justification){
            case 'left':
                return;
                break;
            case 'right':
                $x+=$width-$actual;
                break;
            case 'center':
            case 'centre':
                $x+=($width-$actual)/2;
                break;
            case 'full':
                // count the number of words
                $words = explode(' ',$text);
                $nspaces=count($words)-1;
                if ($nspaces>0){
                    $adjust = ($width-$actual)/$nspaces;
                } else {
                    $adjust=0;
                }
                break;
        }
    }

    /**
     * add text to the page, but ensure that it fits within a certain width
     * if it does not fit then put in as much as possible, splitting at word boundaries
     * and return the remainder.
     * justification and angle can also be specified for the text
     * @access public
     */
    public function addTextWrap($x, $y, $width, $size, $text, $justification = 'left', $angle = 0, $test = 0){
        // this will display the text, and if it goes beyond the width $width, will backtrack to the
        // previous space or hyphen, and return the remainder of the text.

        // $justification can be set to 'left','right','center','centre','full'

        // need to store the initial text state, as this will change during the width calculation
        // but will need to be re-set before printing, so that the chars work out right
        $store_currentTextState = $this->currentTextState;

        if (!$this->numFonts) {
            $this->selectFont(dirname(__FILE__) . '/fonts/Helvetica');
        }
        if ($width<=0){
            // error, pretend it printed ok, otherwise risking a loop
            return '';
        }
        $w=0;
        $break=0;
        $breakWidth=0;
        $len=strlen($text);
        $cf = $this->currentFont;
        $tw = $width/$size*1000;
        for ($i=0;$i<$len;$i++){
            $f=1;
            $directive = $this->checkTextDirective($text,$i,$f);
            if ($directive){
                if ($f){
                    $this->setCurrentFont();
                    $cf = $this->currentFont;
                }
                $i=$i+$directive-1;
            } else {
                $cOrd = ord($text[$i]);
                if (isset($this->fonts[$cf]['differences'][$cOrd])){
                    // then this character is being replaced by another
                    $cOrd2 = $this->fonts[$cf]['differences'][$cOrd];
                } else {
                    $cOrd2 = $cOrd;
                }

                if (isset($this->fonts[$cf]['C'][$cOrd2])){
                    $w+=$this->fonts[$cf]['C'][$cOrd2];
                }
                if ($w>$tw){
                    // then we need to truncate this line
                    if ($break>0){
                        // then we have somewhere that we can split :)
                        if ($text[$break]==' '){
                            $tmp = substr($text,0,$break);
                        } else {
                            $tmp = substr($text,0,$break+1);
                        }
                        $adjust=0;
                        $this->adjustWrapText($tmp,$breakWidth,$width,$x,$adjust,$justification);

                        // reset the text state
                        $this->currentTextState = $store_currentTextState;
                        $this->setCurrentFont();
                        if (!$test){
                            $this->addText($x,$y,$size,$tmp,$angle,$adjust);
                        }
                        return substr($text,$break+1);
                    } else {
                        // just split before the current character
                        $tmp = substr($text,0,$i);
                        $adjust=0;
                        $ctmp=ord($text[$i]);
                        if (isset($this->fonts[$cf]['differences'][$ctmp])){
                            $ctmp=$this->fonts[$cf]['differences'][$ctmp];
                        }
                        $tmpw=($w-$this->fonts[$cf]['C'][$ctmp])*$size/1000;
                        $this->adjustWrapText($tmp,$tmpw,$width,$x,$adjust,$justification);
                        // reset the text state
                        $this->currentTextState = $store_currentTextState;
                        $this->setCurrentFont();
                        if (!$test){
                            $this->addText($x,$y,$size,$tmp,$angle,$adjust);
                        }
                        return substr($text,$i);
                    }
                }
                if ($text[$i]=='-'){
                    $break=$i;
                    $breakWidth = $w*$size/1000;
                }
                if ($text[$i]==' '){
                    $break=$i;
                    $ctmp=ord($text[$i]);
                    if (isset($this->fonts[$cf]['differences'][$ctmp])){
                        $ctmp=$this->fonts[$cf]['differences'][$ctmp];
                    }
                    $breakWidth = ($w-$this->fonts[$cf]['C'][$ctmp])*$size/1000;
                }
            }
        }
        // then there was no need to break this line
        if ($justification=='full'){
            $justification='left';
        }
        $adjust=0;
        $tmpw=$w*$size/1000;
        $this->adjustWrapText($text,$tmpw,$width,$x,$adjust,$justification);
        // reset the text state
        $this->currentTextState = $store_currentTextState;
        $this->setCurrentFont();
        if (!$test){
            $this->addText($x,$y,$size,$text,$angle,$adjust,$angle);
        }
        return '';
    }

    /**
     * this will be called at a new page to return the state to what it was on the
     * end of the previous page, before the stack was closed down
     * This is to get around not being able to have open 'q' across pages
     * @access public
     */
    public function saveState($pageEnd=0){
        if ($pageEnd){
            // this will be called at a new page to return the state to what it was on the
            // end of the previous page, before the stack was closed down
            // This is to get around not being able to have open 'q' across pages
            $opt = $this->stateStack[$pageEnd]; // ok to use this as stack starts numbering at 1
            $this->setColor($opt['col']['r'],$opt['col']['g'],$opt['col']['b'],1);
            $this->setStrokeColor($opt['str']['r'],$opt['str']['g'],$opt['str']['b'],1);
            $this->objects[$this->currentContents]['c'].="\n".$opt['lin'];
            // $this->currentLineStyle = $opt['lin'];
        } else {
            $this->nStateStack++;
            $this->stateStack[$this->nStateStack]=array(
                'col'=>$this->currentColour
                ,'str'=>$this->currentStrokeColour
                ,'lin'=>$this->currentLineStyle
            );
        }
        $this->objects[$this->currentContents]['c'].="\nq";
    }

    /**
     * restore a previously saved state
     * @access public
     */
    public function restoreState($pageEnd=0){
        if (!$pageEnd){
            $n = $this->nStateStack;
            $this->currentColour = $this->stateStack[$n]['col'];
            $this->currentStrokeColour = $this->stateStack[$n]['str'];
            $this->objects[$this->currentContents]['c'].="\n".$this->stateStack[$n]['lin'];
            $this->currentLineStyle = $this->stateStack[$n]['lin'];
            unset($this->stateStack[$n]);
            $this->nStateStack--;
        }
        $this->objects[$this->currentContents]['c'].="\nQ";
    }

    /**
     * make a loose object, the output will go into this object, until it is closed, then will revert to
     * the current one.
     * this object will not appear until it is included within a page.
     * the function will return the object number
     * @access public
     */
    public function openObject(){
        $this->nStack++;
        $this->stack[$this->nStack]=array('c'=>$this->currentContents,'p'=>$this->currentPage);
        // add a new object of the content type, to hold the data flow
        $this->numObj++;
        $this->o_contents($this->numObj,'new');
        $this->currentContents=$this->numObj;
        $this->looseObjects[$this->numObj]=1;

        return $this->numObj;
    }

	/**
	* open an existing object for editing
	* @access public
	*/
	public function reopenObject($id){
	   $this->nStack++;
	   $this->stack[$this->nStack]=array('c'=>$this->currentContents,'p'=>$this->currentPage);
	   $this->currentContents=$id;
	   // also if this object is the primary contents for a page, then set the current page to its parent
	   if (isset($this->objects[$id]['onPage'])){
	     $this->currentPage = $this->objects[$id]['onPage'];
	   }
	}

    /**
     * close an object
     * @access public
     */
    public function closeObject(){
        // close the object, as long as there was one open in the first place, which will be indicated by
        // an objectId on the stack.
        if ($this->nStack>0){
            $this->currentContents=$this->stack[$this->nStack]['c'];
            $this->currentPage=$this->stack[$this->nStack]['p'];
            $this->nStack--;
            // easier to probably not worry about removing the old entries, they will be overwritten
            // if there are new ones.
        }
    }

    /**
     * stop an object from appearing on pages from this point on
     * @access public
     */
    public function stopObject($id){
        // if an object has been appearing on pages up to now, then stop it, this page will
        // be the last one that could contian it.
        if (isset($this->addLooseObjects[$id])){
            $this->addLooseObjects[$id]='';
        }
    }

    /**
     * after an object has been created, it wil only show if it has been added, using this function.
     * @access public
     */
    public function addObject($id,$options='add'){
        // add the specified object to the page
        if (isset($this->looseObjects[$id]) && $this->currentContents!=$id){
            // then it is a valid object, and it is not being added to itself
            switch($options){
            case 'all':
                // then this object is to be added to this page (done in the next block) and
                // all future new pages.
                $this->addLooseObjects[$id]='all';
            case 'add':
                if (isset($this->objects[$this->currentContents]['onPage'])){
                    // then the destination contents is the primary for the page
                    // (though this object is actually added to that page)
                    $this->o_page($this->objects[$this->currentContents]['onPage'],'content',$id);
                }
                break;
            case 'even':
                $this->addLooseObjects[$id]='even';
                $pageObjectId=$this->objects[$this->currentContents]['onPage'];
                if ($this->objects[$pageObjectId]['info']['pageNum']%2==0){
                    $this->addObject($id); // hacky huh :)
                }
                break;
            case 'odd':
                $this->addLooseObjects[$id]='odd';
                $pageObjectId=$this->objects[$this->currentContents]['onPage'];
                if ($this->objects[$pageObjectId]['info']['pageNum']%2==1){
                    $this->addObject($id); // hacky huh :)
                }
                break;
            case 'next':
                $this->addLooseObjects[$id]='all';
                break;
            case 'nexteven':
                $this->addLooseObjects[$id]='even';
                break;
            case 'nextodd':
                $this->addLooseObjects[$id]='odd';
                break;
            }
        }
    }

    /**
     * add content to the documents info object
     * @access public
     */
    public function addInfo($label,$value=0){
        // this will only work if the label is one of the valid ones.
        // modify this so that arrays can be passed as well.
        // if $label is an array then assume that it is key=>value pairs
        // else assume that they are both scalar, anything else will probably error
        if (is_array($label)){
            foreach ($label as $l=>$v){
                $this->o_info($this->infoObject,$l,$v);
            }
        } else {
            $this->o_info($this->infoObject,$label,$value);
        }
    }

    /**
     * set the viewer preferences of the document, it is up to the browser to obey these.
     * @access public
     */
    public function setPreferences($label,$value=0){
        // this will only work if the label is one of the valid ones.
        if (is_array($label)){
            foreach ($label as $l=>$v){
                $this->o_catalog($this->catalogId,'viewerPreferences',array($l=>$v));
            }
        } else {
            $this->o_catalog($this->catalogId,'viewerPreferences',array($label=>$value));
        }
    }

    /**
     * extract an integer from a position in a byte stream
     *
     * @access private
     */
    private function getBytes(&$data,$pos,$num){
        // return the integer represented by $num bytes from $pos within $data
        $ret=0;
        for ($i=0;$i<$num;$i++){
            $ret=$ret*256;
            $ret+=ord($data[$pos+$i]);
        }
        return $ret;
    }

    /**
     * reads the PNG chunk
     * @param $data - binary part of the png image
     * @access private
     */
    private function readPngChunks(&$data){
    	$default = array('info'=> array(), 'transparency'=> null, 'idata'=> null, 'pdata'=> null, 'haveHeader'=> false);
    	// set pointer
		$p = 8;
		$len = strlen($data);
		// cycle through the file, identifying chunks
		while ($p<$len){
			$chunkLen = $this->getBytes($data,$p,4);
			$chunkType = substr($data,$p+4,4);
			//error_log($chunkType. ' - '.$chunkLen);
			switch($chunkType){
				case 'IHDR':
				//this is where all the file information comes from
				$default['info']['width']=$this->getBytes($data,$p+8,4);
				$default['info']['height']=$this->getBytes($data,$p+12,4);
				$default['info']['bitDepth']=ord($data[$p+16]);
				$default['info']['colorType']=ord($data[$p+17]);
				$default['info']['compressionMethod']=ord($data[$p+18]);
				$default['info']['filterMethod']=ord($data[$p+19]);
				$default['info']['interlaceMethod']=ord($data[$p+20]);
				
				$this->debug('readPngChunks: ColorType is' . $default['info']['colorType'], E_USER_NOTICE);
				
				$default['haveHeader'] = true;
				
				if ($default['info']['compressionMethod']!=0){
					$error = true;
					$errormsg = "unsupported compression method";
				}
				if ($default['info']['filterMethod']!=0){
					$error = true;
					$errormsg = "unsupported filter method";
				}
				
				$default['transparency'] = array('type'=> null, 'data' => null);
				
				if ($default['info']['colorType'] == 3) { // indexed color, rbg
					// corresponding to entries in the plte chunk
					// Alpha for palette index 0: 1 byte
					// Alpha for palette index 1: 1 byte
					// ...etc...
		
					// there will be one entry for each palette entry. up until the last non-opaque entry.
					// set up an array, stretching over all palette entries which will be o (opaque) or 1 (transparent)
					$default['transparency']['type']='indexed';
					//$numPalette = strlen($default['pdata'])/3;
					$trans=0;
					for ($i=$chunkLen;$i>=0;$i--){
						if (ord($data[$p+8+$i])==0){
							$trans=$i;
						}
					}
					$default['transparency']['data'] = $trans;
		
				} elseif($default['info']['colorType'] == 0) { // grayscale
					// corresponding to entries in the plte chunk
					// Gray: 2 bytes, range 0 .. (2^bitdepth)-1
		
					// $transparency['grayscale']=$this->getBytes($data,$p+8,2); // g = grayscale
					$default['transparency']['type']='indexed';
					$default['transparency']['data'] = ord($data[$p+8+1]);
				} elseif($default['info']['colorType'] == 2) { // truecolor
					// corresponding to entries in the plte chunk
					// Red: 2 bytes, range 0 .. (2^bitdepth)-1
					// Green: 2 bytes, range 0 .. (2^bitdepth)-1
					// Blue: 2 bytes, range 0 .. (2^bitdepth)-1
					$default['transparency']['r']=$this->getBytes($data,$p+8,2); // r from truecolor
					$default['transparency']['g']=$this->getBytes($data,$p+10,2); // g from truecolor
					$default['transparency']['b']=$this->getBytes($data,$p+12,2); // b from truecolor
				} else if($default['info']['colorType'] == 6 || $default['info']['colorType'] == 4) {
					// set transparency type to "alpha" and proceed with it in $this->o_image later
					$default['transparency']['type'] = 'alpha';
					
					$img = imagecreatefromstring($data);
					
					$imgalpha = imagecreate($default['info']['width'], $default['info']['height']);
					// generate gray scale palette (0 -> 255)
					for ($c = 0; $c < 256; ++$c) {
						ImageColorAllocate($imgalpha, $c, $c, $c);
					}
					// extract alpha channel
					for ($xpx = 0; $xpx < $default['info']['width']; ++$xpx) {
						for ($ypx = 0; $ypx < $default['info']['height']; ++$ypx) {
							$colorBits = imagecolorat($img, $xpx, $ypx);
							$color = imagecolorsforindex($img, $colorBits);
							$color['alpha'] = (((127 - $color['alpha']) / 127) * 255);
							imagesetpixel($imgalpha, $xpx, $ypx, $color['alpha']);
						}
					}
					$tmpfile_alpha=tempnam($this->tempPath,'ezImg');
					
					imagepng($imgalpha, $tmpfile_alpha);
					imagedestroy($imgalpha);
					
					$alphaData = file_get_contents($tmpfile_alpha);
					// nested method call to receive info on alpha image
					$alphaImg = $this->readPngChunks($alphaData);
					// use 'pdate' to fill alpha image as "palette". But it s the alpha channel
					$default['pdata'] = $alphaImg['idata'];
					
					// generate true color image with no alpha channel
					$tmpfile_tt=tempnam($this->tempPath,'ezImg');
		        	
					$imgplain = imagecreatetruecolor($default['info']['width'], $default['info']['height']);
					imagecopy($imgplain, $img, 0, 0, 0, 0, $default['info']['width'], $default['info']['height']);
					imagepng($imgplain, $tmpfile_tt);
					imagedestroy($imgplain);
					
					$ttData = file_get_contents($tmpfile_tt);
					$ttImg = $this->readPngChunks($ttData);
					
					$default['idata'] = $ttImg['idata'];
					
					// remove temp files 
					unlink($tmpfile_alpha);
					unlink($tmpfile_tt);
					// return to addPngImage prematurely. IDAT has already been read and PLTE is not required
					return $default;
				}
				break;
				case 'PLTE':
					$default['pdata'] = substr($data,$p+8,$chunkLen); 
				break;
				case 'IDAT':
					$default['idata'] .= substr($data,$p+8,$chunkLen);
				break;
				case 'tRNS': // this HEADER info is optional. More info: rfc2083 (http://tools.ietf.org/html/rfc2083)
					// error_log('OPTIONAL HEADER -tRNS- exist:');
					// this chunk can only occur once and it must occur after the PLTE chunk and before IDAT chunk
					// KS End new code
				break;
				default:
				break;
			}
			$p += $chunkLen+12;
		}
    	return $default;
    }
    
    /**
     * add a PNG image into the document, from a file
     * this should work with remote files
     * @access public
     */
    public function addPngFromFile($file,$x,$y,$w=0,$h=0){
        // read in a png file, interpret it, then add to the system
        $error = false;
    	$errormsg = "";
        
        $this->debug('addPngFromFile: opening image ' . $file);
        
        $data = file_get_contents($file);
        
        if($data === false){
        	$this->debug('addPngFromFile: trouble opening file ' . $file, E_USER_WARNING);
        	return;
        }
        
		$header = chr(137).chr(80).chr(78).chr(71).chr(13).chr(10).chr(26).chr(10);
		if (substr($data,0,8)!=$header){
			$this->debug('addPngFromFile: Invalid PNG header for file: ' . $file, E_USER_WARNING);
			return;
        }
		
		$iChunk = $this->readPngChunks($data);
		
		if(!$iChunk['haveHeader']){
			$error = true;
			$errormsg = "information header is missing.";
		}
		if (isset($iChunk['info']['interlaceMethod']) && $iChunk['info']['interlaceMethod']){
			$error = true;
			$errormsg = "There appears to be no support for interlaced images in pdf.";
		}
		
        if ($iChunk['info']['bitDepth'] > 8){
        	$error = true;
			$errormsg = "only bit depth of 8 or less is supported.";
        }

		if ($iChunk['info']['colorType'] == 1 || $iChunk['info']['colorType'] == 5 || $iChunk['info']['colorType']== 7){
			$error = true;
			$errormsg = 'Unsupported PNG color type: '.$iChunk['info']['colorType'];
		} else if(isset($iChunk['info'])) {
			switch ($iChunk['info']['colorType']){
				case 3:
					$color = 'DeviceRGB';
					$ncolor=1;
				break;
				case 6:
				case 2:
					$color = 'DeviceRGB';
					$ncolor=3;
				break;
				case 4:
				case 0:
					$color = 'DeviceGray';
					$ncolor=1;
				break;
			}
		}
            
        if ($error){
            $this->debug('addPngFromFile: '.$errormsg, E_USER_WARNING);
            return;
        }
        if ($w==0){
            $w=$h/$iChunk['info']['height']*$iChunk['info']['width'];
        }
        if ($h==0){
            $h=$w*$iChunk['info']['height']/$iChunk['info']['width'];
        }
        
        if($this->hashed){
        	$oHash = md5($iChunk['idata']);
        }
    	if(isset($oHash) && isset($this->objectHash[$oHash])){
    		$label = $this->objectHash[$oHash];
    	}else{
    		$this->numImages++;
        	$label='I'.$this->numImages;
        	$this->numObj++;
        	
    		if(isset($oHash)){
    			$this->objectHash[$oHash] = $label;
    		}
    		
    		$options = array('label'=>$label,'data'=>$iChunk['idata'],'bitsPerComponent'=>$iChunk['info']['bitDepth'],'pdata'=>$iChunk['pdata']
                                      ,'iw'=>$iChunk['info']['width'],'ih'=>$iChunk['info']['height'],'type'=>'png','color'=>$color,'ncolor'=>$ncolor);
        	if (isset($iChunk['transparency'])){
	            $options['transparency']=$iChunk['transparency'];
    	    }
        	$this->o_image($this->numObj,'new',$options);
    	}
    	
        $this->objects[$this->currentContents]['c'].="\nq ".sprintf('%.3F',$w)." 0 0 ".sprintf('%.3F',$h)." ".sprintf('%.3F',$x)." ".sprintf('%.3F',$y)." cm";
        $this->objects[$this->currentContents]['c'].=" /".$label.' Do';
        $this->objects[$this->currentContents]['c'].=" Q";
    }
    
    /**
     * add a JPEG image into the document, from a file
     * @access public
     */
    public function addJpegFromFile($img,$x,$y,$w=0,$h=0){
        // attempt to add a jpeg image straight from a file, using no GD commands
        // note that this function is unable to operate on a remote file.

        if (!file_exists($img)){
            return;
        }

        $tmp=getimagesize($img);
        $imageWidth=$tmp[0];
        $imageHeight=$tmp[1];

        if (isset($tmp['channels'])){
            $channels = $tmp['channels'];
        } else {
            $channels = 3;
        }

        if ($w<=0 && $h<=0){
            $w=$imageWidth;
        }
        if ($w==0){
            $w=$h/$imageHeight*$imageWidth;
        }
        if ($h==0){
            $h=$w*$imageHeight/$imageWidth;
        }

        $data = file_get_contents($img);

        $this->addJpegImage_common($data,$x,$y,$w,$h,$imageWidth,$imageHeight,$channels);
    }

    /**
     * read gif image from file, converts it into an JPEG (no transparancy) and display it
     * @param $img - file path ti gif image
     * @param $x - coord x
     * @param $y - y cord
     * @param $w - width
     * @param $h - height
     * @access public
     */
	public function addGifFromFile($img, $x, $y, $w=0, $h=0){
		if (!file_exists($img)){
            return;
        }
        
        if(!function_exists("imagecreatefromgif")){
        	$this->debug('addGifFromFile: Missing GD function imageCreateFromGif', E_USER_ERROR);
        	return;
        }
        
        $tmp=getimagesize($img);
        $imageWidth=$tmp[0];
        $imageHeight=$tmp[1];
        
        
        if ($w<=0 && $h<=0){
            $w=$imageWidth;
        }
        if ($w==0){
            $w=$h/$imageHeight*$imageWidth;
        }
        if ($h==0){
            $h=$w*$imageHeight/$imageWidth;
        }
        
        $imgres = imagecreatefromgif($img);
        $tmpName=tempnam($this->tempPath,'img');
        imagejpeg($imgres,$tmpName,90);
        
        $this->addJpegFromFile($tmpName,$x,$y,$w,$h);
	}
	
	 /**
     * add an image into the document, from a GD object
     * this function is not all that reliable, and I would probably encourage people to use
     * the file based functions
     * @param $img - gd image resource
     * @param $x coord x
     * @param $y coord y
     * @param $w width
     * @param $h height
     * @param $quality image quality
     * @access protected
     */
    protected function addImage(&$img,$x,$y,$w=0,$h=0,$quality=75){
        // add a new image into the current location, as an external object
        // add the image at $x,$y, and with width and height as defined by $w & $h

        // note that this will only work with full colour images and makes them jpg images for display
        // later versions could present lossless image formats if there is interest.

        // there seems to be some problem here in that images that have quality set above 75 do not appear
        // not too sure why this is, but in the meantime I have restricted this to 75.
        if ($quality>75){
            $quality=75;
        }

        // if the width or height are set to zero, then set the other one based on keeping the image
        // height/width ratio the same, if they are both zero, then give up :)
        $imageWidth=imagesx($img);
        $imageHeight=imagesy($img);

        if ($w<=0 && $h<=0){
            return;
        }
        if ($w==0){
            $w=$h/$imageHeight*$imageWidth;
        }
        if ($h==0){
            $h=$w*$imageHeight/$imageWidth;
        }

        $tmpName=tempnam($this->tempPath,'img');
        imagejpeg($img,$tmpName,$quality);
        
        $data = file_get_contents($tmpName);
        if($data === false) {
            $this->debug('addImage: trouble opening image resource', E_USER_WARNING);
        }
        unlink($tmpName);
        $this->addJpegImage_common($data,$x,$y,$w,$h,$imageWidth,$imageHeight);
    }

    /**
     * common code used by the two JPEG adding functions
     * @access private
     */
    private function addJpegImage_common(&$data,$x,$y,$w=0,$h=0,$imageWidth,$imageHeight,$channels=3){
        // note that this function is not to be called externally
        // it is just the common code between the GD and the file options
        if($this->hashed){
        	$oHash = md5($data);
        }
    	if(isset($oHash) && isset($this->objectHash[$oHash])){
    		$label = $this->objectHash[$oHash];
    	}else{
    		$this->numImages++;
        	$label='I'.$this->numImages;
        	$this->numObj++;
        	
    		if(isset($oHash)){
    			$this->objectHash[$oHash] = $label;
    		}
    		
        	$this->o_image($this->numObj,'new',array('label'=>$label,'data'=>$data,'iw'=>$imageWidth,'ih'=>$imageHeight,'channels'=>$channels));
    	}

        $this->objects[$this->currentContents]['c'].="\nq ".sprintf('%.3F',$w)." 0 0 ".sprintf('%.3F',$h)." ".sprintf('%.3F',$x)." ".sprintf('%.3F',$y)." cm";
        $this->objects[$this->currentContents]['c'].=" /".$label.' Do';
        $this->objects[$this->currentContents]['c'].=" Q";
    }

    /**
     * specify where the document should open when it first starts
     * @access public
     */
    public function openHere($style,$a=0,$b=0,$c=0){
        // this function will open the document at a specified page, in a specified style
        // the values for style, and the required paramters are:
        // 'XYZ'  left, top, zoom
        // 'Fit'
        // 'FitH' top
        // 'FitV' left
        // 'FitR' left,bottom,right
        // 'FitB'
        // 'FitBH' top
        // 'FitBV' left
        $this->numObj++;
        $this->o_destination($this->numObj,'new',array('page'=>$this->currentPage,'type'=>$style,'p1'=>$a,'p2'=>$b,'p3'=>$c));
        $id = $this->catalogId;
        $this->o_catalog($id,'openHere',$this->numObj);
    }

    /**
     * create a labelled destination within the document
     * @access public
     */
    public function addDestination($label,$style,$a=0,$b=0,$c=0){
        // associates the given label with the destination, it is done this way so that a destination can be specified after
        // it has been linked to
        // styles are the same as the 'openHere' function
        $this->numObj++;
        $this->o_destination($this->numObj,'new',array('page'=>$this->currentPage,'type'=>$style,'p1'=>$a,'p2'=>$b,'p3'=>$c));
        $id = $this->numObj;
        // store the label->idf relationship, note that this means that labels can be used only once
        $this->destinations["$label"]=$id;
    }

    /**
     * define font families, this is used to initialize the font families for the default fonts
     * and for the user to add new ones for their fonts. The default bahavious can be overridden should
     * that be desired.
     * @access public
     */
    public function setFontFamily($family, $options = ''){
        if (is_array($options)) {
            // the user is trying to set a font family
            // note that this can also be used to set the base ones to something else
            if (strlen($family)){
                $this->fontFamilies[$family] = $options;
            }
        }
    }

    /**
     * used to add messages for use in debugging
     * @access protected
     */
    protected function debug($message, $error_type = E_USER_NOTICE)
    {
    	if($error_type <= $this->DEBUGLEVEL){
	    	switch(strtolower($this->DEBUG)){
	    		default:
	    		case 'none':
	    			break;
	    		case 'error_log':
	    			trigger_error($message, $error_type);
	    			break;
	    		case 'variable':
	    			$this->messages.=$message."\n";
	    		break;
	    	}
    	}
    }

    /**
     * a few functions which should allow the document to be treated transactionally.
     *
     * @param string $action WHAT IS THIS?
     * @return void
     * @access protected
     */
    public function transaction($action){
        switch ($action){
        case 'start':
            // store all the data away into the checkpoint variable
            $data = get_object_vars($this);
            $this->checkpoint = $data;
            unset($data);
            break;
        case 'commit':
            if (is_array($this->checkpoint) && isset($this->checkpoint['checkpoint'])){
                $tmp = $this->checkpoint['checkpoint'];
                $this->checkpoint = $tmp;
                unset($tmp);
            } else {
                $this->checkpoint='';
            }
            break;
        case 'rewind':
            // do not destroy the current checkpoint, but move us back to the state then, so that we can try again
            if (is_array($this->checkpoint)){
                // can only abort if were inside a checkpoint
                $tmp = $this->checkpoint;
                foreach ($tmp as $k=>$v){
                    if ($k != 'checkpoint'){
                        $this->$k=$v;
                    }
                }
                unset($tmp);
            }
            break;
        case 'abort':
            if (is_array($this->checkpoint)){
                // can only abort if were inside a checkpoint
                $tmp = $this->checkpoint;
                foreach ($tmp as $k=>$v){
                    $this->$k=$v;
                }
                unset($tmp);
            }
            break;
        }
    }
} // end of class
?>