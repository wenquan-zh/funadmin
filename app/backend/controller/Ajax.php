<?php
/**
 * FunAadmin
 * ============================================================================
 * 版权所有 2017-2028 FunAadmin，并保留所有权利。
 * 网站地址: https://www.FunAadmin.cn
 * ----------------------------------------------------------------------------
 * 采用最新Thinkphp6实现
 * ============================================================================
 * Author: yuege
 * Date: 2017/8/2
 */
namespace app\backend\controller;

use app\backend\model\AuthRule;
use app\backend\service\AuthService;
use app\common\controller\Backend;
use app\common\model\Attach as AttachModel;
use app\common\service\OssService;
use app\common\service\UploadService;
use app\common\traits\Curd;
use fun\helper\DataHelper;
use fun\helper\FileHelper;
use think\App;
use think\Exception;
use think\facade\Cache;
use think\facade\Lang;
use getID3;

class Ajax extends Backend
{

    use Curd;

    //上传验证规则
    protected $uploadValidate = [
        'file' => 'filesize:102400|fileExt:jpg,png,gif,jpeg,rar,zip,avi,mp4,rmvb,3gp,flv,mp3,txt,doc,xls,ppt,pdf,xls,docx,xlsx,doc'
    ];
    protected $imageValidate = [
        'image' => 'filesize:10240|fileExt:jpg,png,gif,jpeg,bmp,svg,webp'

    ];
    protected $videoValidate = [
        'video' => 'filesize:10240|avi,rmvb,3gp,flv,mp4'

    ];
    protected $voiceValidate = [
        'voice' => 'filesize:2048|mp3,wma,wav,amr'

    ];
    protected $driver = 'local';

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->modelClass = new AttachModel();
        $this->driver = syscfg('upload','uplad_driver');
        $fileExt = syscfg('upload','upload_file_type');
        $filemax = syscfg('upload', 'upload_file_max') * 1024;
        $this->uploadValidate = ['file' => 'filesize:' . $filemax . '|fileExt:' . $fileExt,];

    }
    /**
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * 文件上传总入口 集成qiniu ali tenxunoss
     */
    public function uploads()
    {
//        try {
//            $upload = UploadService::instance();
//            $result = $upload->uploads();
//            return json($result);
//
//        }catch(Exception $e){
//            $this->error($e->getMessage());
//        }

        //获取上传文件表单字段名
        $type = $this->request->param('type', 'file');
        $path = $this->request->param('path', 'uploads');
        $files = request()->file();
        $uploadService = OssService::instance();
        //        $file =request()->file('file') ? request()->file('file'): request()->file('upfile');
        foreach ($files as $k => $file) {
            $file_size = $file->getSize();
            $original_name = $file->getOriginalName();
            $md5 = $file->md5();
            $sha1 = $file->sha1();;
            $file_mime = $file->getMime();
            $attach = $this->modelClass->where('md5', $md5)->find();
            var_dump(validate($validate)->check(DataHelper::objToArray($file)));die;
            if (!$attach) {
                try {
                    switch ($type == 'file') {
                        case 'file':
                            $validate = $this->uploadValidate;
                            break;
                        case 'image':
                            $validate = $this->imageValidate;
                            break;
                        case 'video':
                            $validate = $this->videoValidate;
                            break;
                        case 'voice' :
                            $validate = $this->voiceValidate;
                            break;
                        default:
                            $validate = $this->uploadValidate;

                    }

                    $savename = \think\facade\Filesystem::disk('public')->putFile($path, $file);
                    $path = DS . 'storage' . DS . $savename;
                    $paths = trim($path, '/');
                    //整合上传接口 获取视频音频长度
                    $getID3 = new getID3();
                    $analyzeFileInfo = $getID3->analyze('./'.$path);
                    $duration = isset($analyzeFileInfo['playtime_seconds'])?$analyzeFileInfo['playtime_seconds']:0;
                    if ($this->driver == 'alioss') {
                        $path = $uploadService->alioss($paths, './' . $paths);
                    } elseif ($this->driver == 'qiniuoss') {
                        $path = $uploadService->qiniuoss($paths, './' . $paths);
                    } elseif ($this->driver == 'teccos') {
                        $path = $uploadService->teccos($paths, './' . $paths);
                    }
                }catch (\think\exception\ValidateException $e) {
                    $path = '';
                    $error = $e->getMessage();
                }catch (Exception $e){
                        $this->error($e->getMessage());
                }
                $file_ext = strtolower(substr($savename, strrpos($savename, '.') + 1));
                $file_name = basename($savename);
                $width = $height = 0;
                if (in_array($file_mime, ['image/gif', 'image/jpg', 'image/jpeg', 'image/bmp', 'image/png', 'image/webp']) || in_array($file_ext, ['gif', 'jpg', 'jpeg', 'bmp', 'png', 'webp'])) {
                    $imgInfo = getimagesize($file->getPathname());;
                    if (!$imgInfo || !isset($imgInfo[0]) || !isset($imgInfo[1])) {
                        $this->error(lang('Uploaded file is not a valid image'));
                    }
                    $width = isset($imgInfo[0]) ? $imgInfo[0] : $width;
                    $height = isset($imgInfo[1]) ? $imgInfo[1] : $height;
                }
                if (!empty($path)) {
                    $data = [
                        'admin_id' => session('admin.id'),
                        'name' => $file_name,
                        'original_name' => $original_name,
                        'path' => $path,
                        'thumb' => $path,
                        'url' => $this->driver == 'local' ? $this->request->domain() . $path : $path,
                        'ext' => $file_ext,
                        'size' => $file_size / 1024,
                        'width' => $width,
                        'height' => $height,
                        'duration' => $duration,
                        'md5' => $md5,
                        'sha1' => $sha1,
                        'mime' => $file_mime,
                        'driver' => $this->driver,

                    ];
                    $attach = AttachModel::create($data);
                    $result['data'][] = $attach->path; //兼容wangeditor
                    $result['id'] = $attach->id;
                    $result["url"] = $path;
                } else {
                    //上传失败获取错误信息
                    $result['url'] = '';
                    $result['msg'] = $error;
                    $result['code'] = 0;
                    $result['state'] = 'ERROR'; //兼容百度
                    $result['errno'] = 'ERROR'; //兼容wangeditor
                    return json($result);
                }

            } else {
                $result['data'][] = $attach->path; //兼容wangeditor
                $result['id'] = $attach->id;
                $result['fileType'] = $type;
                $result["url"] = $attach->path;
            }
        }

        $result['state'] = 'SUCCESS'; //兼容百度
        $result['errno'] = 0; //兼容wangeditor
        $result['code'] = 1;//默认
        $result['msg'] = lang('upload success');
        return json($result);
    }


    /**
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * 刷新菜单
     */
    public function refreshmenu(){
        $cate = AuthRule::where('menu_status', 1)->order('sort asc')->select()->toArray();
        $menulsit = (new AuthService())->menuhtml($cate);
        $this->success($menulsit);

    }
    /**
     * @return \think\response\Jsonp
     * 自动加载语言函数
     */
    public function lang()
    {
        header('Content-Type: application/javascript');
        $controllername = $this->request->get("controllername");
        $controllername = strtolower(parse_name($controllername,1));
        $addon = $this->request->param('addons');
        //默认只加载了控制器对应的语言名，你还根据控制器名来加载额外的语言包
        $this->loadlang($controllername,$addon);
        return jsonp(Lang::get())->code(200)->options([
                    'var_jsonp_handler'     => 'callback',
                    'default_jsonp_handler' => 'jsonpReturn',
                    'json_encode_param'     => JSON_PRETTY_PRINT | JSON_FORCE_OBJECT | JSON_UNESCAPED_UNICODE,
                ])->allowCache(true)->expires(3600);
    }

    /**
     * @return \think\response\Json
     * 获取图片列表
     */
    public function getList()
    {
        $path = $this->request->param('path', 'uploads');
        $paths = app()->getRootPath() . 'public/storage/' . $path;
        $type = $this->request->param('type', 'image');
        $list = FileHelper::getFileList($paths, $type);
        $data = ['state' => 'SUCCESS', 'start' => 0, 'total' => count($list), 'list' => []];
        $attach = AttachModel::where('mime', 'like', '%' . 'image' . '%')->select()->toArray();
        if ($list) {
            foreach ($list[0] as $k => $v) {
                $data['list'][$k]['url'] = str_replace(app()->getRootPath() . 'public', '', $v);
                $data['list'][$k]['mtime'] = mime_content_type($v);
            }
        }
        $data['list'] = array_merge($data['list'], $attach);
        return json($data);
    }

    /**
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * 获取附件列表
     */
    public function getAttach()
    {
        if ($this->request->isAjax()) {
            list($this->page, $this->pageSize, $sort, $where) = $this->buildParames();
            $count = $this->modelClass
                ->where($where)
                ->order($sort)
                ->count();
            $list = $this->modelClass->where($where)
                ->where($where)
                ->order($sort)
                ->page($this->page, $this->pageSize)
                ->select();
            $result = ['code' => 0, 'msg' => lang('Get Data Success'), 'data' => $list, 'count' => $count];
            return json($result);
        }

    }

    /*
     * 清除缓存
    */
    public function clearcache()
    {
        Cache::clear();
        $this->success('清除成功');
    }

}