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
			$oModuleModel = getModel('module');
			$oDocumentModel = getModel('document');
			Context::set('oModuleModel',$oModuleModel);
			Context::set('oDocumentModel',$oDocumentModel);

			// 정렬 대상
			$valid_order_targets = ['list_order', 'update_order', 'regdate', 'rand()', 'voted_count', 'readed_count', 'comment_count'];
			$args->order_target = in_array($args->order_target ?? '', $valid_order_targets) ? $args->order_target : 'list_order';
			
			$obj = new stdClass();
			$obj->sort_index = $args->order_target;

			// 정렬 순서
			$order_type = $args->order_type ?? 'asc';
			if(!in_array($order_type, ['asc', 'desc'])) $order_type = 'asc';
			$obj->order_type = $order_type == "desc" ? "asc" : "desc";

			// 총 목록 수
			$list_count = (int)($args->list_count ?? 5);
			if(!$list_count) $list_count = 5;
			$obj->list_count = $list_count;

			// 설정값들의 기본값 처리
			$defaults = [
				'subject_cut_size' => 0,
				'content_cut_size' => 300,
				'thumbnail_type' => 'fill',
				'thumbnail_width' => 250,
				'thumbnail_height' => 250,
				'top_title_size' => '24px',
				'title_size' => '20px',
				'sum_font_size' => '14px',
				'title_color' => '#444',
				'sum_font_color' => '#444',
				'title_font_color' => '#444',
				'content_l_h' => '1.5',
				'content_h_n' => 6,
				'tooltip_color' => null,
				'tooltip_location' => null,
				'back_color' => 'transparent',
				'w_size' => '95%',
				'mh_n' => 'one',
				// YouTube 설정값
				'youtube_eid' => 'transfer',
				'option_margin' => 0,
				// Slide 설정값
				'view_no' => 2,
				'scroll_no' => 1,
				'autospeed' => 5000,
				'speed' => 3000,
				'm_no' => 2,
				'rows' => 1,
				'top_box_height' => 300,
				'center_padding' => '50px',
				// Box 옵션
				'border_size' => 0,
				'border_color' => '#f5f5f5'
			];

			// 최근 글 표시 시간
			if(!$args->duration_new) $args->duration_new = 24;
			$widget_info = new stdClass();
			$widget_info->duration_new = $duration_new * 60 * 60;

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