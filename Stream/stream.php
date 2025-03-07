<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Module\Stream\Domain\PostGateway;
use Gibbon\Module\Stream\Domain\PostTagGateway;
use Gibbon\Module\Stream\Domain\PostAttachmentGateway;
use Gibbon\Module\Stream\Domain\CategoryGateway;
use Gibbon\Module\Stream\Domain\CategoryViewedGateway;

if (isActionAccessible($guid, $connection2, '/modules/Stream/stream.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
    return;
} else {
    // Proceed!
    $urlParams = [
        'streamCategoryID' => $_REQUEST['streamCategoryID'] ?? '',
        'category' => $_REQUEST['category'] ?? '',
        'tag'      => $_REQUEST['tag'] ?? '',
        'user'     => $_REQUEST['user'] ?? '',
    ];

    $page->scripts->add('magnific', 'modules/Stream/js/magnific/jquery.magnific-popup.min.js');
    $page->stylesheets->add('magnific', 'modules/Stream/js/magnific/magnific-popup.css');

    $page->breadcrumbs
        ->add(__m('View Stream'), 'stream.php');

    if (!empty($urlParams['category'])) {
        $page->breadcrumbs->add(__('Category').': '.$urlParams['category']);
    } else if (!empty($urlParams['tag']) || !empty($urlParams['user'])) {
        $page->breadcrumbs->add(__('Viewing {filter}', [
            'filter' => !empty($urlParams['user']) ? $urlParams['user'] : '#' . $urlParams['tag']
        ]));
    }

    // QUERY
    $postGateway = $container->get(PostGateway::class);
    $categoryGateway = $container->get(CategoryGateway::class);
    $categoryViewedGateway = $container->get(CategoryViewedGateway::class);
    $gibbonSchoolYearID = $session->get('gibbonSchoolYearID');

    $criteria = $postGateway->newQueryCriteria(true)
        ->sortBy(['timestamp'], 'DESC')
        ->filterBy('category', $urlParams['streamCategoryID'])
        ->filterBy('tag', $urlParams['tag'])
        ->filterBy('user', $urlParams['user'])
        ->pageSize(15)
        ->fromPOST();

    // Get the stream, join a set of attachment data per post
    $showPreviousYear = $container->get(SettingGateway::class)->getSettingByScope('Stream', 'showPreviousYear');
    $stream = $postGateway->queryPostsBySchoolYear($criteria, $gibbonSchoolYearID, $showPreviousYear, null, $session->get('gibbonRoleIDCurrent'));
    $streamPosts = $stream->getColumn('streamPostID');
    $attachments = $container->get(PostAttachmentGateway::class)->selectAttachmentsByPost($streamPosts)->fetchGrouped();
    $stream->joinColumn('streamPostID', 'attachments', $attachments);

    // Get viewable categories
    $categories = $categoryGateway->selectViewableCategoriesByPerson($session->get('gibbonPersonID'))->fetchGroupedUnique();
    if (!empty($categories)) {
        $categories = array_merge([0 => ['name' => __('All'), 'streamCategoryID' => 0]], $categories);
    }

    if (!empty($urlParams['streamCategoryID']) && empty($categories[$urlParams['streamCategoryID']])) {
        $page->addError(__('You do not have access to this action.'));
        return;
    }

    $currentCategory = $categories[$urlParams['streamCategoryID']] ?? [];

    // Update current category timestamp
    if (!empty($currentCategory)) {
        $data = [
            'gibbonPersonID' => $session->get('gibbonPersonID'),
            'streamCategoryID' => $currentCategory['streamCategoryID'],
            'timestamp' => date('Y-m-d H:i:s'),
        ];
        $updated = $categoryViewedGateway->insertAndUpdate($data, ['timestamp' => date('Y-m-d H:i:s')]);
    }


    $streamData = array_map(function ($item) {
        // Auto-link urls
        $item['post'] = preg_replace_callback('/(https?:\/\/[^\s\$.?#].[^\s]*)(\s|$)+/iu', function ($matches) {
            $linktext = strlen($matches[1]) > 45 ? substr($matches[1], 0, 45).'…' : $matches[1];
            return Format::link($matches[1], $linktext).' ';
        }, $item['post']);

        // Auto-link hashtags
        $item['post'] = preg_replace_callback('/(?:\s|^)+([#]+)([\w]+)($|\b)+/iu', function ($matches) {
            return ' '.Format::link('./index.php?q=/modules/Stream/stream.php&tag=' . $matches[2], $matches[1] . $matches[2]).$matches[3];
        }, $item['post']);

        return $item;
    }, $stream->toArray());

    // RENDER STREAM
    echo $page->fetchFromTemplate('stream.twig.html', [
        'stream' => $streamData,
        'pageNumber' => $stream->getPage(),
        'pageCount' => $stream->getPageCount(),
        'categories' => $categories,
        'currentCategory' => $currentCategory,
    ]);

    $categoryGateway = $container->get(CategoryGateway::class);
    $categories = $categoryGateway->selectPostableCategoriesByRole($session->get('gibbonRoleIDCurrent'))->fetchKeyPair();

    $sidebarExtra = '';

    // NEW POST
    // Ensure user has access to post in this category
    $canPost = empty($urlParams['streamCategoryID']) || !empty($categories[$urlParams['streamCategoryID']]);
    if ($canPost && isActionAccessible($guid, $connection2, '/modules/Stream/posts_manage_add.php')) {
        $form = Form::create('post', $session->get('absoluteURL').'/modules/Stream/posts_manage_addProcess.php');
        $form->addHiddenValue('address', $session->get('address'));
        $form->addHiddenValue('source', 'stream');
        $form->addHiddenValue('streamCategoryID', $urlParams['streamCategoryID']);
        $form->addClass('form-small');
        $form->removeMeta();

        $postLength = $container->get(SettingGateway::class)->getSettingByScope('Stream', 'postLength');
        $col = $form->addRow()->addColumn()->addClass('font-sans text-sm');
            $col->addTextArea('post')->setID('newPost')->required()->setRows(6)->maxLength($postLength);

        $row = $form->addRow()->addDetails()->summary(__('Add Photos'));
            $row->addFileUpload('attachments')->accepts('.jpg,.jpeg,.gif,.png')->uploadMultiple(true);

        // Categories
        if (!empty($urlParams['streamCategoryID'])) {
            $form->addHiddenValue('streamCategoryIDList', $urlParams['streamCategoryID']);
        } else if (!empty($categories)) {
            $row = $form->addRow()->addDetails()->summary(__('Categories'));
                $row->addCheckbox('streamCategoryIDList')->fromArray($categories);
        }

        $row = $form->addRow()->addSubmit(__('Post'));

        $formHTML = '<div class="column-no-break">';
        $formHTML .= '<h5 class="mt-4 mb-2 text-xs pb-0 ">';
        $formHTML .= !empty($urlParams['category'])
            ? __('New Post in {category}', ['category' => $urlParams['category']])
            : __('New Post');
        $formHTML .= '</h5>';
        $formHTML .= $form->getOutput();
        $formHTML .= '</div>';

        $sidebarExtra .= $formHTML;
    }

    // RECENT TAGS
    $tags = $container->get(PostTagGateway::class)->selectRecentTagsBySchoolYear($gibbonSchoolYearID)->fetchAll(\PDO::FETCH_COLUMN, 0);
    $sidebarExtra .= $page->fetchFromTemplate('tags.twig.html', [
        'tags' => $tags,
    ]);

    $session->set('sidebarExtra', $sidebarExtra);
}
?>
<script>
    $(document).ready(function() {
        var pageNum = <?php echo $stream->getPage(); ?>;
        var pageTotal = <?php echo $stream->getPageCount(); ?>;

        $('#loadPosts').click(function() {
            pageNum++;

            $.ajax({
                url: "<?php echo $session->get('absoluteURL'); ?>/modules/Stream/streamAjax.php",
                data: {
                    streamCategoryID: '<?php echo $urlParams['streamCategoryID']; ?>',
                    page: pageNum,
                },
                type: 'POST',
                success: function(data) {
                    if (data) {
                        $('#stream').append(data);

                        if (pageNum >= pageTotal) {
                            $('#loadPosts').remove();
                        }
                    }
                },
            });
        });

        $('.image-container').magnificPopup({
            type: 'image',
            delegate: 'a',
            gallery: {
                enabled: true
            },
            image: {
                titleSrc: 'data-caption',
            }
        });
    });
</script>
