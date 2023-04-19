{*
* 2007-2021 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2021 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}


<div class="panel">
	<div class="panel-heading">Settings Main</div>
	<div class="moduleconfig-content">
		<form method="post">
			<div class="row">
				<div class="col-xs-12">
					<div class="form-group row">
						<label class="control-label required">
							Back-End URL of PrestApp
						</label>
						<div class="col-sm">
							<input name="PRESTAPP_V2_MODULE_API_BACKEND_URL" class="col-md-5 form-control" type="text" value="{$api_backend_url}">
						</div>
					</div>


					<div class="form-group row">
						<label class="control-label required">
							Shop schema
						</label>
						<div class="col-sm">
							<input name="PRESTAPP_V2_MODULE_API_SCHEMA_SHOP" class="col-md-5 form-control" type="text" value="{$api_schema_shop}">
						</div>
					</div>


					<div class="form-group row">
						<label class="control-label required">
							Shop name
						</label>
						<div class="col-sm">
							<input name="PRESTAPP_V2_MODULE_API_ID_SHOPNAME" class="col-md-5 form-control" type="text" value="{$api_id_shopname}">
						</div>
					</div>


					<div class="form-group row">
						<label class="control-label required">
							Shop URL
						</label>
						<div class="col-sm">
							<input name="PRESTAPP_V2_MODULE_API_URL" class="col-md-5 form-control" type="text" value="{$api_url}">
						</div>
					</div>


					<div class="form-group row">
						<label class="control-label required">
							Shop KEY
						</label>
						<div class="col-sm">
							<input name="PRESTAPP_V2_MODULE_API_KEY" class="col-md-5 form-control" type="text" value="{$api_key}">
						</div>
					</div>

					<div class="form-group row">
						<label class="control-label required">
							Onesignal key
						</label>
						<div class="col-sm">
							<input name="PRESTAPP_v2_MODULE_API_ONESIGNAL_ID" class="col-md-5 form-control" type="text" value="{$api_onesignal_id}">
						</div>
					</div>
				</div>
			</div>
		</form>
	</div>
</div>
<div class="panel">
	<div class="panel-heading">Settings for add your shop to back-end Prestapp</div>
	<div class="moduleconfig-content">
		<form method="post">
			<div class="row">
				<div class="col-xs-12">
					<div class="form-group row">
						<label class="control-label">
							Show Combinations with only reference (1 or 0)
						</label>
						<div class="col-sm">
							<input name="PRESTAPP_V2_MODULE_API_BACKEND_ONLY_EAN13" class="col-md-5 form-control" type="text" value="{$api_only_ean13}">
						</div>
					</div>


					<div class="form-group row">
						<label class="control-label required">
							Your email
						</label>
						<div class="col-sm">
							<input name="PRESTAPP_V2_MODULE_API_BACKEND_EMAIL" class="col-md-5 form-control" type="text" value="{$api_email}">
						</div>
					</div>


					<div class="form-group row">
						<label class="control-label required">
							IP
						</label>
						<div class="col-sm">
							<input name="PRESTAPP_V2_MODULE_API_BACKEND_IP" class="col-md-5 form-control" type="text" value="{$api_ip}">
						</div>
					</div>


					<div class="form-group row">
						<label class="control-label required">
							Link of your shop
						</label>
						<div class="col-sm">
							<input name="PRESTAPP_V2_MODULE_API_BACKEND_LINK_SHOP" class="col-md-5 form-control" type="text" value="{$api_link_shop}">
						</div>
					</div>


					<div class="form-group row">
						<label class="control-label required">
							Your phone
						</label>
						<div class="col-sm">
							<input name="PRESTAPP_V2_MODULE_API_BACKEND_MOBILE" class="col-md-5 form-control" type="text" value="{$api_mobile}">
						</div>
					</div>

					<div class="panel-footer">
						<button type="submit" value="1" id="configuration_form_submit_btn_1" name="submit_prestapp_v2_module" class="btn btn-default pull-right">
							<i class="process-icon-save"></i> Submit
						</button>
					</div>
				</div>
			</div>
		</form>
	</div>
</div>

{*{if isset($confirmation)}*}
{*  <div class="alert alert-success">Settings updated</div>*}
{*{/if}*}

{*{if isset($errors)}*}
{*	<div class="alert alert-danger">All fields with (*) are required</div>*}
{*{/if}*}