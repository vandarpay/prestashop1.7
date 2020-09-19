{extends "$layout"}

{block name="content"}

<h2 class="page-heading">{l s='وضعیت سفارش:' mod='vandar'}&nbsp;{$orderReference|escape:'htmlall':'UTF-8'}</h2>

{literal}
    <style type="text/css">
        #statusMessageContainer {
            text-align: center;
        }
        
        #statusMessageContainer p {
            text-align: left;
        }
        
        /* Loader */
        .loading {
            position: relative;
            width: 100%;
            height: 70px;
        }

        .loading:after {
            font-family: Sans-Serif !important;
            box-sizing: border-box;
            content: '';
            position: absolute;
            z-index: 100;
            left: 50%;
            top: 50%;
            width: 40px;
            height: 40px;
            font-size: 40px;
            border-right: 3px solid #9e191d;
            border-bottom: 1px solid #ebebeb;
            border-top: 2px solid #9e191d;
            border-radius: 100px;
            margin: -30px 0 0 -20px; 
            animation: spin .75s infinite linear;
            -webkit-animation: spin .75s infinite linear;
            -moz-animation: spin .75s infinite linear;
            -o-animation: spin .75s infinite linear;
        }

        .spin {
            -webkit-animation: spin 1000ms infinite linear;
            animation: spin 1000ms infinite linear;
        }

        @keyframes spin {
            from { transform:rotate(0deg); }
            to { transform:rotate(360deg); }
        }

        @-webkit-keyframes spin {
            from { -webkit-transform: rotate(0deg); }
            to { -webkit-transform: rotate(360deg); }
        }
        
        #hiddenHookData {
            display: none;
        }
    </style>
{/literal}     
<div id="statusMessageContainer">
    {if $message != null}
        <p class="alert alert-danger">{$message|escape:'htmlall':'UTF-8'}</p>
        {literal}
            <script type="text/javascript">
                setTimeout(function(){location.href="{/literal}{$redirectUrl}{literal}";}, 4000);
            </script>
        {/literal}
    {/if}
</div>

<a href="{$link->getPageLink('index', true, null)}" class="btn btn-warning vandar-back-button">{l s='Back to main mage' mod='vandar'}</a>
{/block}