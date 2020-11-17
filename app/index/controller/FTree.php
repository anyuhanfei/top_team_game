<?php
/**
 * 通用树类.
 * Date: 2016/2/3
 * Time: 7:57
 */

namespace app\index\controller;



class FTree {
    private static $_instance = null;

    /**
     * 生成树型结构所需要的2维数组
     * @var array
     */
    public $arr;

    /**
     * 生成树型结构所需修饰符号，可以换成图片
     * @var array
     */
    public $icon = array('│', '├', '└');
    public $nbsp = "&nbsp;";

    /**
     * 树地图
     * @var array
     */
    private $maps;
    private $p_str;
    private $s_str;
    /**
     * @access private
     */
    public $ret = '';

    /**
     * 初始化
     * @return self
     */
    public static function getInstance() {
        if (!self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * 构造函数，初始化类
     * @param array $data 2维数组，例如：
     * array(
     *      1 => array('sid'=>'1','pid'=>0,'name'=>'一级栏目一'),
     *      2 => array('sid'=>'2','pid'=>0,'name'=>'一级栏目二'),
     *      3 => array('sid'=>'3','pid'=>1,'name'=>'二级栏目一'),
     *      4 => array('sid'=>'4','pid'=>1,'name'=>'二级栏目二'),
     *      5 => array('sid'=>'5','pid'=>2,'name'=>'二级栏目三'),
     *      6 => array('sid'=>'6','pid'=>3,'name'=>'三级栏目一'),
     *      7 => array('sid'=>'7','pid'=>3,'name'=>'三级栏目二')
     * )
     * @return HTree
     */
    public function __construct($data = array(),$p_str,$s_str) {
        $this->setData($data,$p_str,$s_str);
    }

    /**
     * 设置树数据
     * @param array $data
     * @return Tree
     */
    public function setData($data,$p_str,$s_str) {
        $this->p_str=$p_str;
        $this->s_str=$s_str;
        foreach ($data as $v) {
            $this->arr[$v[$s_str]] = $v;
            $this->maps[$v[$p_str]][] = $v[$s_str];
        }
        return $this;
    }

    /**
     * 获取分类树
     * @param  $id 父ID
     * @param integer $mx_depth 获取深度 0 所有
     * @param boolean $returnPlane 返回平面数据
     * @param string $childrenName 子菜单KEY
     * @param integer $depth 深度内部使用
     * @return array
     */
    public function getTree($id = 0, $mx_depth = 0, $returnPlane = FALSE, $childrenName='children',$depth = -1) {
        $depth++;
        $mx_depth = ($mx_depth == 0) ? count($this->maps) : $mx_depth;
        $sections = array();
        if ($mx_depth > $depth && isset($this->maps[$id])) {
            $count = count($this->maps[$id]) - 1;
            foreach ($this->maps[$id] as $key => $childId) {

                $child = $this->arr[$childId];
                $child['first'] = $key == 0;
                $child['last'] = $key == $count;
                $child['depth'] = $depth;
                if ($returnPlane) {

                    if (isset($this->maps[$childId])) {
                        $child['ischildren'] = true;
                    };
                    $sections[] = $child;
                    $sections = array_merge($sections, $this->getTree($childId, $mx_depth, $returnPlane,$childrenName, $depth));
                } else {
                    if (isset($this->maps[$childId])) {
                        $child['ischildren'] = true;
                        $child[$childrenName] = $this->getTree($childId, $mx_depth, $returnPlane, $childrenName,$depth);
                    }
                    $sections[] = $child;
                }
            }
        }
        return $sections;
    }

    /**
     * 获取子级子级ID
     * @param type $id 树id
     * @param type $mx_depth 深度默认0 最大深度
     * @param type $inSelf 是否包含自己
     * @param type $depth 内部参数禁止赋值
     * @return array ids
     */
    public function getChildIds($id = 0, $mx_depth = 0, $inSelf = true, $depth = -1) {
        $depth++;
        $mx_depth = ($mx_depth == 0) ? count($this->maps) : $mx_depth;
        $sections = array();
        if ($inSelf)
            $sections[] = $id;
        if ($mx_depth > $depth && isset($this->maps[$id])) {
            foreach ($this->maps[$id] as $childId) {
                $sections = array_merge($sections, $this->getChildIds($childId, $mx_depth, true, $depth));
            }
        }
        return $sections;
    }

    public function getPids($id) {
        $sections = array();
        if(isset($this->arr[$id]) && $this->arr[$id][$this->p_str] != 0) {
            $arr = $this->arr[$this->arr[$id][$this->p_str]];
            $sections[] = $arr[$this->s_str];
            if(isset($this->arr[$arr[$this->p_str]])) {
                $sections = array_merge($sections, $this->getPids($arr[$this->s_str]));
            }
        }
        return $sections;
    }

    /**
     * 得到直推
     * @param int
     * @return array
     */
    public function get_child($myid) {
        $newarr = array();
        if (is_array($this->arr)) {
            foreach ($this->arr as $id => $a) {
                if ($a[$this->p_str] == $myid)
                    $newarr[$id] = $a[$this->s_str];
            }
        }
        return $newarr;
    }
}