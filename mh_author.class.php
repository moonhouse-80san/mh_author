<?php
	/**
	 * @class mh_author
	 * @author zero (zero@nzeo.com)
	 * @brief 최근 게시물을 출력하는 위젯
	 * @version 0.1
	 **/

	class mh_author extends WidgetHandler {

		/**
		 * @brief 위젯의 실행 부분
		 *
		 * ./widgets/위젯/conf/info.xml 에 선언한 extra_vars를 args로 받는다
		 * 결과를 만든후 print가 아니라 return 해주어야 한다
		 **/
			function proc($args) {

			//기본 객체 설정
			$oModuleModel = &getModel('module');
			$oDocumentModel = &getModel('document');
			Context::set('oModuleModel',$oModuleModel);
			Context::set('oDocumentModel',$oDocumentModel);

			// 정렬 대상
			if(!in_array($args->order_target, array('list_order','update_order','regdate','rand()','voted_count','readed_count'))) $args->order_target = 'list_order';
			//if ($args->order_target == 'random') $args->order_target = 'rand()';
			$obj->sort_index = $args->order_target;

			// 정렬 순서
			$order_type = $args->order_type;
			if(!in_array($order_type, array('asc','desc'))) $order_type = 'asc';
			$obj->order_type = $order_type=="desc"?"asc":"desc";

			$list_count = (int)$args->list_count;
			if(!$list_count) $list_count = 10;
			$obj->list_count = $list_count;

			// 설정값
			$args->subject_cut_size = isset($args->subject_cut_size) ? (int)$args->subject_cut_size : 0;
			$args->content_cut_size = isset($args->content_cut_size) ? (int)$args->content_cut_size : 300;
			$args->thumbnail_type = isset($args->thumbnail_type) ? $args->thumbnail_type : 'fill';
			$args->thumbnail_width = isset($args->thumbnail_width) ? (int)$args->thumbnail_width : 250;
			$args->thumbnail_height = isset($args->thumbnail_height) ? (int)$args->thumbnail_height : 250;
			$args->top_title_size = isset($args->top_title_size) ? $args->top_title_size : '24px';
			$args->title_size = isset($args->title_size) ? $args->title_size : '20px';
			$args->sum_font_size = isset($args->sum_font_size) ? $args->sum_font_size : '14px';
			$args->title_color = isset($args->title_color) ? $args->title_color : '#444';
			$args->sum_font_color = isset($args->sum_font_color) ? $args->sum_font_color : '#444';
			$args->title_font_color = isset($args->title_font_color) ? $args->title_font_color : '#444';
			$args->content_l_h = isset($args->content_l_h) ? $args->content_l_h : '1.5';
			$args->content_h_n = isset($args->content_h_n) ? (int)$args->content_h_n : 6;
			$args->tooltip_color = $args->tooltip_color;
			$args->tooltip_location = $args->tooltip_location;
			$args->back_color = isset($args->back_color) ? $args->back_color : 'transparent';
			$args->w_size = isset($args->w_size) ? $args->w_size : '95%';
			$args->mh_n = isset($args->mh_n) ? $args->mh_n : 'one';

			// YouTube 설정값
			$args->youtube_eid = isset($args->youtube_eid) ? $args->youtube_eid : 'transfer';
			$args->option_margin = isset($args->option_margin) ? (int)$args->option_margin : 0;

			// Slide 설정값
			$args->view_no = isset($args->view_no) ? (int)$args->view_no : 2;
			$args->scroll_no = isset($args->scroll_no) ? (int)$args->scroll_no : 1;
			$args->autospeed = isset($args->autospeed) ? (int)$args->autospeed : 5000;
			$args->speed = isset($args->speed) ? (int)$args->speed : 3000;
			$args->m_no = isset($args->m_no) ? (int)$args->m_no : 2;
			$args->rows = isset($args->rows) ? (int)$args->rows : 1;
			$args->top_box_height = isset($args->top_box_height) ? (int)$args->top_box_height : 300;
			$args->center_padding = isset($args->center_padding) ? $args->center_padding : '50px';

			// 최근 글 표시 시간
			$widget_info->duration_new = $duration_new * 60*60;
			if(!$args->duration_new) $args->duration_new = 24;

			// Box 옵션
			$args->border_size = isset($args->border_size) ? (int)$args->border_size : 0;
			$args->border_color = isset($args->border_color) ? $args->border_color : '#f5f5f5';

			// 대상 모듈
			$mid_list = explode(",",$args->mid_list);

			// module_srl 대신 mid가 넘어왔을 경우는 직접 module_srl을 구해줌
			if($mid_list) {
				$oModuleModel = &getModel('module');
				$module_srl = $oModuleModel->getModuleSrlByMid($mid_list);
			}

			// 대상 모듈 (mid_list는 기존 위젯의 호환을 위해서 처리하는 루틴을 유지. module_srl로 위젯에서 변경)
			if($args->mid_list) {
				$mid_list = explode(",",$args->mid_list);
				$oModuleModel = &getModel('module');
				if(count($mid_list)) {
					$module_srl = $oModuleModel->getModuleSrlByMid($mid_list);
				} else {
					$site_module_info = Context::get('site_module_info');
					if($site_module_info) {
						$margs->site_srl = $site_module_info->site_srl;
						$oModuleModel = &getModel('module');
						$output = $oModuleModel->getMidList($margs);
						if(count($output)) $mid_list = array_keys($output);
						$module_srl = $oModuleModel->getModuleSrlByMid($mid_list);
					}
				}
			} else $module_srl = explode(',',$args->module_srls);

			// DocumentModel::getDocumentList()를 이용하기 위한 변수 정리
			if(is_array($module_srl)) $obj->module_srl = implode(',',$module_srl);
			else $obj->module_srl = $module_srl;

			$obj->category_srl = $args->category_srl;
			$output = executeQueryArray('widgets.mh_author.getMhMulti', $obj);
			$output_count = executeQueryArray('widgets.mh_author.getNewestDocumentsCount', $obj);

			// document 모듈의 model 객체를 받아서 결과를 객체화 시킴
			$oDocumentModel = &getModel('document');

			// 오류가 생기면 그냥 무시
			if(!$output->toBool()) return;

			// 결과가 있으면 각 문서 객체화를 시킴
			if($order_type == "RAND") shuffle($output->data);
			if(count($output->data)) {
				foreach($output->data as $key => $attribute) {
					$document_srl = $attribute->document_srl;

					$oDocument = null;
					$oDocument = new documentItem();
					$oDocument->setAttribute($attribute);

					if($args->view_category == "on") {
						$oDocument->category = @$oDocumentModel->getCategory($attribute->category_srl);
						$oDocument->category_srl = $attribute->category_srl;
					}

					$oModuleInfo = $oModuleModel->getModuleInfoByModuleSrl($attribute->module_srl);
					$oDocument->mid = $oModuleInfo->mid;

					// 확장 변수 출력
					if($args->extra_var_num) {
						$oDocument->extra_value = $oDocument->getExtraValue($args->extra_var_num);
						$oDocument->extra_var_num = $args->extra_var_num;
					}

					// 모듈이 여러개일 때 메뉴 명 구함
					if((count($mid_list) > 1 || !$args->mid_list) && $args->view_menuname == "on") {
						// 탭 이름 설정
						switch($args->menuname) {
							case "browser_title" : $oDocument->menuname = $oModuleInfo->browser_title; break;
							case "title" : $oDocument->menuname = $oModuleInfo->title; break;
						}
					}

					$document_list[$key] = $oDocument;

					/* 출력되는 모듈의 번호를 구함 */
					$document_modulies[$attribute->module_srl] = $attribute->module_srl;
				}
			} else {
				$document_list = array();
			}

			// 템플릿 파일에서 사용할 변수들을 세팅
			if(count($mid_list)==1) $args->module_name = $mid_list[0];

			$args->document_list = $document_list;

			Context::set('box_list',$output->data);
			Context::set('widget_info', $args);

			// 템플릿의 스킨 경로를 지정 (skin, colorset에 따른 값을 설정)
			$tpl_path = sprintf('%sskins/%s', $this->widget_path, $args->skin);
			Context::set('colorset', $args->colorset);

			// 템플릿 파일을 지정
			$tpl_file = 'list';

			// 템플릿 컴파일
			$oTemplate = &TemplateHandler::getInstance();
			$output = $oTemplate->compile($tpl_path, $tpl_file);
			return $output;
		}
	}

?>