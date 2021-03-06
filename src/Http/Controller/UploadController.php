<?php namespace Anomaly\FileFieldType\Http\Controller;

use Anomaly\FileFieldType\Table\FileTableBuilder;
use Anomaly\FileFieldType\Table\UploadTableBuilder;
use Anomaly\FilesModule\File\FileUploader;
use Anomaly\FilesModule\Folder\Command\GetFolder;
use Anomaly\FilesModule\Folder\Contract\FolderInterface;
use Anomaly\FilesModule\Folder\Contract\FolderRepositoryInterface;
use Anomaly\Streams\Platform\Http\Controller\AdminController;
use Illuminate\Foundation\Bus\DispatchesJobs;

/**
 * Class UploadController
 *
 * @link   http://pyrocms.com/
 * @author PyroCMS, Inc. <support@pyrocms.com>
 * @author Ryan Thompson <ryan@pyrocms.com>
 */
class UploadController extends AdminController
{

    use DispatchesJobs;

    /**
     * Return the uploader.
     *
     * @param UploadTableBuilder $table
     * @param $folder
     * @return \Illuminate\Contracts\View\View|mixed
     */
    public function index(UploadTableBuilder $table, $folder)
    {
        /* @var FolderInterface $folder */
        $folder = dispatch_now(new GetFolder($folder));

        $config = cache('file-field_type::' . request('key'), []);

        $allowed = array_intersect(array_get($config, 'allowed_types', []), $folder->getAllowedTypes());

        return $this->view->make(
            'anomaly.field_type.file::upload/index',
            [
                'allowed' => $allowed ?: $folder->getAllowedTypes(),
                'table'   => $table->make()->getTable(),
                'folder'  => $folder,
                'config'  => $config,
            ]
        );
    }

    /**
     * Upload a file.
     *
     * @param  FileUploader $uploader
     * @param  FolderRepositoryInterface $folders
     * @return \Illuminate\Http\JsonResponse
     */
    public function upload(FileUploader $uploader, FolderRepositoryInterface $folders)
    {
        if ($file = $uploader->upload($this->request->file('upload'), $folders->find($this->request->get('folder')))) {
            return $this->response->json($file->getAttributes());
        }

        return $this->response->json(['message' => 'There was a problem uploading the file.'], 500);
    }

    /**
     * Return the recently uploaded files.
     *
     * @param  FileTableBuilder $table
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function recent(UploadTableBuilder $table)
    {
        return $table->setUploaded(explode(',', $this->request->get('uploaded')))
            ->make()
            ->getTableContent();
    }
}
