<?php

class XMLParser  {
    
    // raw xml
    private $rawXML;
    // xml parser
    private $parser = null;
    // array returned by the xml parser
    private $valueArray = array();
    
    // return data
    private $output = array();
    private $flat_output = array();
    private $dot_path_list = array();

    private $status;

    public function XMLParser($xml){
        $this->rawXML = $xml;
        $this->parser = xml_parser_create();
        return $this->mai_parse();
        return $this->parse();
    }

    private function mai_parse(){

        $parser = $this->parser;
        
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0); // Dont mess with my cAsE sEtTings
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);     // Dont bother with empty info
        if(!xml_parse_into_struct($parser, $this->rawXML, $this->valueArray)){
            $this->status = 'error: '.xml_error_string(xml_get_error_code($parser)).' at line '.xml_get_current_line_number($parser);
            return false;
        }
        xml_parser_free($parser);

        $position = array();
        $root = array();
        $flat_root = array();
        $relative_root = &$root;

        foreach ($this->valueArray as $index => $element) {

            //echo "###############################################"."\n";
            //var_dump($element);

            $level = $element['level'];
            $tag = str_replace(":", "_", $element['tag']);
            $type = $element['type'];

            // create array to keep position of xml like array('rootElement', 'elementLevel_1', 'elementLevel_2', ...)
            $length_of_position = count($position);
            if ($level > $length_of_position) {
                array_push($position, $tag);
            }
            else if ($level < $length_of_position) {
                $position = array_slice($position, 0, $level);
                $length_of_position = count($position);
                $position[$length_of_position-1] = $tag;
            }
            else {
                $position[$length_of_position-1] = $tag;
            }

            // var_dump($position);
            $position_path_string = implode(".", $position);

            $relative_root = &$root;
            $pos_length = count($position);

            // build xml value array recursively
            // pattern: $root(['tagName'][0])+['_value']
            foreach ($position as $pos_idx => $tagName) {
                if (!isset($relative_root[$tagName])) {
                    $relative_root[$tagName][0] = array();
                    $relative_root = &$relative_root[$tagName][0];
                }
                else {
                    $element_counter = count($relative_root[$tagName]);
                    if ($type == 'open' && ($pos_length - 1 == $pos_idx)) {
                        $relative_root[$tagName][] = array();
                        $relative_root = &$relative_root[$tagName][$element_counter];
                    }
                    else {
                        $relative_root = &$relative_root[$tagName][$element_counter-1];
                    }
                }
            }

            if (isset($element['value'])) {
                // set some value
                $relative_root['_value'] = $element['value'];
                $flat_data = array();
                $flat_data[$position_path_string] = $element['value'];
                $this->dot_path_list[$position_path_string] = true;
                $flat_root[] = $flat_data;
            }

            if (isset($element['attributes'])) {
                // do something
                foreach ($element['attributes'] as $attr_key => $attr_val) {
                    $attr_key = str_replace(":", "_", $attr_key);

                    $flat_data = array();
                    $flat_data[$position_path_string . "._attributes." . $attr_key] = $attr_val;
                    $this->dot_path_list[$position_path_string . "._attributes." . $attr_key] = true;

                    $relative_root['_attributes'][$attr_key] = $attr_val;
                    $flat_root[] = $flat_data;
                }
            }

            
        }

        $this->flat_output = $flat_root;
        $this->output = $root;
    }
   
    public function getDotPath(){
        return array_keys($this->dot_path_list);
    }

    public function getOutput(){
        return $this->output;
    }
    
    public function getFlatOutput(){
        return $this->flat_output;
    }

    public function getStatus(){
        return $this->status;    
    }
       
}

#$p = new XMLParser($xmldata);
#$o = $p->getOutput();
