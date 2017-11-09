<?php
class Database extends PDO{
    protected static $oPDO = null;

    private $db_info = array();
    private $oSTH = null;
    private $sSQL = '';
    private $sTable = '';
    private $dsn = '';
    private $aData = array();
    private $sWhere = '';
    
    public $iLastId = 0;
    public $iAllLastId = array();
    public $iAffectedRows = 0;
    public $aWhere = array();
    public $aColumn = array();
    public $aResult = array();
    public $aResults = array();

    public function __construct($db_info=null){
        // check params $db_info;
        if(count($db_info)>=1 && $db_info['db_name']){
            $this->db_info['host'] = $db_info['db_host'] ? $db_info['db_host'] : 'localhost';
            $this->db_info['user'] = $db_info['db_user'] ? $db_info['db_user'] : 'root';
            $this->db_info['password'] = $db_info['db_password'] ? $db_info['db_password'] : '';
            $this->db_info['database'] = $db_info['db_name'];
        }elseif(is_string($db_info)){
            $this->db_info['host'] = 'localhost';
            $this->db_info['user'] = 'root';
            $this->db_info['password'] = '';
            $this->db_info['database'] = $db_info;
        }else{
            //die('Invalid Database configuration. Please re-check Database Setting!');
            $this->db_info['host'] = DB_HOST;
            $this->db_info['user'] = DB_USER;
            $this->db_info['password'] = DB_PASSWORD;
            $this->db_info['database'] = DB_NAME;
        }

        // begin connect
        if($this->db_info){
            $this->dsn = 'mysql:host='.$this->db_info['host'].'; dbname='.$this->db_info['database'];
            try {
                parent::__construct('mysql:host='.$this->db_info['host'].'; dbname='.$this->db_info['database'], $this->db_info['user'], $this->db_info['password'], array(
                    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'
                ));
                $this->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT );
                $this->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
                $this->setAttribute( PDO::ATTR_EMULATE_PREPARES, true );
                $this->setAttribute( PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC );
            }
            catch ( PDOException $e ) {
                die('Invalid Database configuration. Please re-check Database Setting!');
            }
        }
    }

    public function __destruct() {
        self::$oPDO = null;
    }

    public static function getPDO( $dsn = array() ) {
        if(!isset(self::$oPDO) || (self::$oPDO!==null)){
            self::$oPDO = new self( $dsn );
        }
        return self::$oPDO;
    }

    public function begin() {
        $this->oPDO->beginTransaction();
    }

    public function end() {
        $this->commit();
    }

    public function rollBack() {
        $this->rollBack();
    }

    public function result($iRow=0){
        return isset($this->aResults[$iRow]) ? $this->aResults[$iRow] : false;
    }

    public function results(){
        return isset($this->aResults) ? $this->aResults : false;
    }

    public function affectedRows() {
        return is_numeric($this->iAffectedRows) ? $this->iAffectedRows : false;
    }

    public function getLastInsertId() {
        return $this->iLastId;
    }

    public function getAllLastInsertId() {
        return $this->iAllLastId;
    }

    function bindParams($aParams=null){
        if(is_array($aParams)){
            foreach($aParams as $key=>$val){
                // get sign & value data
                if(substr($val,0,2)=='<='){
                    $sign = substr($val,0,2);
                    $sval = trim(substr($val, 2, strlen($val)));
                }elseif(substr($val,0,2)=='>='){
                    $sign = substr($val,0,2);
                    $sval = trim(substr($val, 2, strlen($val)));
                }elseif(substr($val,0,2)=='!='){
                    $sign = substr($val,0,2);
                    $sval = trim(substr($val, 2, strlen($val)));
                }elseif(substr($val,0,1)=='<'){
                    $sign = substr($val,0,1);
                    $sval = trim(substr($val, 1, strlen($val)));
                }elseif(substr($val,0,1)=='>'){
                    $sign = substr($val,0,1);
                    $sval = trim(substr($val, 1, strlen($val)));
                }else{
                    $sign = '=';
                    $sval = trim($val);
                }
                if($sval>=0){
                    $this->oSTH->bindParam(':'.$key, strval($sval), PDO::PARAM_INT);
                }else{
                    $this->oSTH->bindParam(':'.$key, $sval, PDO::PARAM_STR);
                }
            }
        }
    }

    function bindValues($aParams=null){
        if(is_array($aParams)){
            foreach($aParams as $key=>$val){
                $this->oSTH->bindValue(':'.$key, $val);
            }
        }
    }

    function getWhere($aParams=null){
        if(count($aParams)>0){
            foreach($aParams as $Fieldkey=>$valData){
                // get sign & value data
                if(substr($valData,0,2)=='<='){
                    $sign = substr($valData,0,2);
                    $sval = trim(substr($valData, 2, strlen($valData)));
                }elseif(substr($valData,0,2)=='>='){
                    $sign = substr($valData,0,2);
                    $sval = trim(substr($valData, 2, strlen($valData)));
                }elseif(substr($valData,0,2)=='!='){
                    $sign = substr($valData,0,2);
                    $sval = trim(substr($valData, 2, strlen($valData)));
                }elseif(substr($valData,0,1)=='<'){
                    $sign = substr($valData,0,1);
                    $sval = trim(substr($valData, 1, strlen($valData)));
                }elseif(substr($valData,0,1)=='>'){
                    $sign = substr($valData,0,1);
                    $sval = trim(substr($valData, 1, strlen($valData)));
                }else{
                    $sign = '=';
                    $sval = trim($valData);
                }
                $aParams['data'][] = $Fieldkey.$sign.':'.$Fieldkey;
                echo "$Fieldkey$sign:$Fieldkey";
            }
            $aParams = implode(' AND ',$aParams['data']);
        }elseif($aParams==null){
            $aParams = '';
        }
        return $aParams;
    }

    public function query($strSql='', $aBindWhereParam=null){
        if($aBindWhereParam){
            $this->sSQL = trim($strSql).' WHERE '.$this->getWhere($aBindWhereParam);
            $this->oSTH = $this->prepare($this->sSQL);
            $this->aWhere = $aBindWhereParam;
            $this->bindParams($aBindWhereParam);
            try{
                if($this->oSTH->execute()){
                    $operation = reset(explode(' ',trim($this->sSQL)));
                    $operation = strtoupper($operation);
                    switch($operation):
                        case 'SELECT':
                            $this->iAffectedRows = $this->oSTH->rowCount();
                            $this->aResults = $this->oSTH->fetchAll();
                            break;
                        case 'INSERT':
                            $this->iLastId = $this->lastInsertId();
                            break;
                        case 'UPDATE':
                            $this->iAffectedRows = $this->oSTH->rowCount();
                            break;
                        case 'DELETE':
                            $this->iAffectedRows = $this->oSTH->rowCount();
                            break;
                    endswitch;
                    $this->oSTH->closeCursor();
                    return $this;
                }
            }
            catch(PDOException $e){
                die($e->getMessage() . ': ' . __LINE__ );
            }
        }else{
            $this->sSQL = trim($strSql);
            $this->oSTH = $this->prepare($this->sSQL);
            try{
                if($this->oSTH->execute()){
                    //$this->iAffectedRows = $this->oSTH->rowCount();
                    //$this->aResults = $this->oSTH->fetchAll();
                    $operation = reset(explode(' ',trim($this->sSQL)));
                    $operation = strtoupper($operation);
                    switch($operation):
                        case 'SELECT':
                            $this->iAffectedRows = $this->oSTH->rowCount();
                            $this->aResults = $this->oSTH->fetchAll();
                            break;
                        case 'INSERT':
                            $this->iLastId = $this->lastInsertId();
                            break;
                        case 'UPDATE':
                            $this->iAffectedRows = $this->oSTH->rowCount();
                            break;
                        case 'DELETE':
                            $this->iAffectedRows = $this->oSTH->rowCount();
                            break;
                    endswitch;
                    $this->oSTH->closeCursor();
                    return $this;
                }
            }
            catch(PDOException $e){
                die($e->getMessage() . ': ' . __LINE__ );
            }
        }
    }

    function insert($sTable, $aData){
        if($sTable){
            if($aData){
                $bind = array();
                foreach($aData as $field=>$value){
                    $bind['field_name'][] = $field;
                    $bind['field_bind'][] = ':'.$field;
                }
                $this->sSQL  = "INSERT INTO `$sTable` (".implode(',', $bind['field_name']).") VALUES (".implode(',', $bind['field_bind']).");";
                $this->oSTH = $this->prepare($this->sSQL);
                $this->bindValues($aData);
                try{
                    if($this->oSTH->execute()){
                        $this->iLastId = $this->lastInsertId();
                        $this->oSTH->closeCursor();
                        return $this;
                    }
                }
                catch(PDOException $e){
                    die($e->getMessage() . ': ' . __LINE__ );
                }
            }else{
                die('Invalid format Data!');
            }
        }else{
            die('Please set specific table want to insert!');
        }
    }

    function insertBatch($sTable, $aData){
        if($sTable){
            if($aData){
                $tmp_sql = array();
                $tmp_field = array();
                $tmp_bind = array();
                $this->beginTransaction();
                foreach($aData as $i=>$dt){
                    foreach($dt as $k=>$v){
                        $tmp_field[] = $k;
                        $tmp_bind[] = ':'.$k;
                    }
                    $tmp_bind = '('.implode(', ',$tmp_bind).')';
                    $tmp_sql[] = $tmp_bind;
                    $this->oSTH = $this->prepare("INSERT INTO `$sTable` (".implode(',', $tmp_field).") VALUES $tmp_bind");
                    $this->bindValues($dt);
                    try{
                        if($this->oSTH->execute()){
                            $this->iAllLastId[] = $this->lastInsertId();
                        }else{
                            $this->rollBack();
                        }
                    }
                    catch(PDOException $e){
                        die($e->getMessage() . ': ' . __LINE__ );
                    }
                    $tmp_field = array();
                    $tmp_bind = array();
                }
                $this->commit();
                if($this->iAllLastId){
                    $this->sSQL  = "INSERT INTO `$sTable` (".implode(', ', array_keys($aData[0])).") VALUES ".implode(', ',$tmp_sql);
                    $this->oSTH->closeCursor();
                    return $this;
                }
            }else{
                die('Invalid format Data!');
            }
        }else{
            die('Please set specific table want to insert!');
        }
    }

    function update($sTable, $aData, $aWhere=array()){
        if($sTable){
            if($aData){
                $bind = array();
                $strWhere = '';
                foreach($aData as $field=>$value){
                    $bind[$field] = "$field=:$field";
                }
                if($aWhere){
                    foreach($aWhere as $Fieldkey=>$valData){
                        // get sign & value data
                        if(substr($valData,0,2)=='<='){
                            $sign = substr($valData,0,2);
                            $sval = trim(substr($valData, 2, strlen($valData)));
                        }elseif(substr($valData,0,2)=='>='){
                            $sign = substr($valData,0,2);
                            $sval = trim(substr($valData, 2, strlen($valData)));
                        }elseif(substr($valData,0,2)=='!='){
                            $sign = substr($valData,0,2);
                            $sval = trim(substr($valData, 2, strlen($valData)));
                        }elseif(substr($valData,0,1)=='<'){
                            $sign = substr($valData,0,1);
                            $sval = trim(substr($valData, 1, strlen($valData)));
                        }elseif(substr($valData,0,1)=='>'){
                            $sign = substr($valData,0,1);
                            $sval = trim(substr($valData, 1, strlen($valData)));
                        }else{
                            $sign = '=';
                            $sval = trim($valData);
                        }
                        $aWhere['data'][] = $Fieldkey.$sign.':'.$Fieldkey;
                        $aData[$Fieldkey] = $valData;
                    }
                    $strWhere = ' WHERE '.implode(' AND ',$aWhere['data']);
                }
                $this->sSQL  = "UPDATE `$sTable` set ".implode(', ', $bind).$strWhere;
                $this->oSTH = $this->prepare($this->sSQL);
                $this->bindValues($aData);
                try{
                    if($this->oSTH->execute()){
                        $this->iAffectedRows = $this->oSTH->rowCount();
                        $this->oSTH->closeCursor();
                        return $this;
                    }
                }
                catch(PDOException $e){
                    die($e->getMessage() . ': ' . __LINE__ );
                }
            }else{
                die('Invalid format Data!');
            }
        }else{
            die('Please set specific table want to update!');
        }
    }

    function delete($sTable, $aWhere=array()){
        if($sTable){
            $bind = array();
            $strWhere = '';
            if($aWhere){
                foreach($aWhere as $Fieldkey=>$valData){
                    // get sign & value data
                    if(substr($valData,0,2)=='<='){
                        $sign = substr($valData,0,2);
                        $sval = trim(substr($valData, 2, strlen($valData)));
                    }elseif(substr($valData,0,2)=='>='){
                        $sign = substr($valData,0,2);
                        $sval = trim(substr($valData, 2, strlen($valData)));
                    }elseif(substr($valData,0,2)=='!='){
                        $sign = substr($valData,0,2);
                        $sval = trim(substr($valData, 2, strlen($valData)));
                    }elseif(substr($valData,0,1)=='<'){
                        $sign = substr($valData,0,1);
                        $sval = trim(substr($valData, 1, strlen($valData)));
                    }elseif(substr($valData,0,1)=='>'){
                        $sign = substr($valData,0,1);
                        $sval = trim(substr($valData, 1, strlen($valData)));
                    }else{
                        $sign = '=';
                        $sval = trim($valData);
                    }
                    $bind[$Fieldkey] = $sval;
                    $aWhere['data'][] = $Fieldkey.$sign.':'.$Fieldkey;
                    $aData[$Fieldkey] = $valData;
                }
                $strWhere = ' WHERE '.implode(' AND ',$aWhere['data']);
            }
            $this->sSQL  = "DELETE from `$sTable`".$strWhere;
            $this->oSTH = $this->prepare($this->sSQL);
            $this->bindValues($bind);
            try{
                if($this->oSTH->execute()){
                    $this->iAffectedRows = $this->oSTH->rowCount();
                    $this->oSTH->closeCursor();
                    return $this;
                }
            }
            catch(PDOException $e){
                die($e->getMessage() . ': ' . __LINE__ );
            }
        }else{
            die('Please set specific table want to delete!');
        }
    }

    function truncate($sTable){
        $this->sSQL = "TRUNCATE TABLE `$sTable`;";
        $this->oSTH = $this->prepare( $this->sSQL );
        try{
            if($this->oSTH->execute()){
                $this->oSTH->closeCursor();
                return true;
            }
        }
        catch(PDOException $e){
            die($e->getMessage() . ': ' . __LINE__ );
        }
    }

    public function drop($sTable){
        $this->sSql = "DROP TABLE `$sTable`;";
        $this->oSTH = $this->prepare( $this->sSql );
        try{
            if($this->oSTH->execute()){
                $this->oSTH->closeCursor();
                return true;
            }
        }
        catch(PDOException $e){
            die($e->getMessage() . ': ' . __LINE__ );
        }
    }
}
