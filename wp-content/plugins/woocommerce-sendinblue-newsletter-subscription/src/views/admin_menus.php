<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@700;800&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <title>WooCommerce After Connect Page</title>
    <style>
      .main {
        height: 100%;
		width:98.5%;
        background: #f5f5f5;
      }
      .rect-top {
        position: relative;
        width: 100%;
        height: 30%;
        left: 0px;
        top: 0px;
        background: #2c2c2c;
      }
      .sib-heading {
		position:relative;
		left:15.5%;
      }
      .sib-text {
        position: relative;
        top: -7px;
        left: 5px;
      }
      .text {
        font-family: "Roboto";
        font-style: normal;
        font-weight: 500;
        font-size: 20px;
        line-height: 24px;
        position: relative;
        color: #ffffff;
        text-shadow: 0px 1px 1px rgba(0, 0, 0, 0.25);
        top: 20px;
      }
      .connect-button {
        background: #0b996e;
        border-radius: 97px;
        width: 10%;
        padding: 15px 7% 15px 7%;
        font-family: 'Roboto';
        font-style: normal;
        font-weight: 400;
        font-size: 20px;
        line-height: 24px;
        color: #fff;
        text-decoration: none;
      }
      .connect-button:hover,
      .connect-button:visited {
        color: #fff;
      }
	  .connect-button:hover {
		background-color: #006a43;
	  }
      .rect-bottom {
        width: 100%;
        height: 70%;
        position: relative;
		
        left: 0px;
        top: 0px;
      }
      .bottom-overlay {
        background-color: #fff;
        position: relative;
        width: 69%;
        height: 80%;
        margin:auto;
		padding-top: 3.3%;
      }
      .do-it-all-with-sendinblue {
        position: relative;
        margin: auto;
        top: 5%;
        text-align: center;
        font-family: "Publico";
        font-style: "normal";
        font-weight: 550;
        font-size: 31px;
        line-height: 40px;
        color: #1f2d3d;
      }
	 
      .features {
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        width: 90%;
        margin: 0% 10% 0% 5%;
        position: relative;
      }
      .email_marketing {
        width: 32%;
        height: 50%;
      }
      .email_marketing_img_overlay {
        position: relative;
        background: #f9fafc;
        border: 1px solid #eff2f7;
        border-radius: 8px;
        text-align: center;
        padding: 15px 40px;
      }
      .email_marketing_content {
        position: relative;
        top: 25px;
      }
      .email_marketing_heading {
        font-family: "Publico";
        font-weight: 550;
        font-size: 20px;
        line-height: 24px;
        color: #1f2d3d;
      }
      .email_marketing_subheading {
        font-family: "Roboto";
        font-style: "normal";
        font-weight: 300;
        font-size: 16px;
        line-height: 24px;
        color: #687484;
        margin-top: 10px;
		padding-right: 20px;
      }
      .hr {
        border: 1px solid #eff2f7;
        position: relative;
        top: 10%;
		margin:7% 0% 4% 0%;
      }
      .for-woocommerce {
        font-family: "Roboto";
        font-style: normal;
        font-weight: 400;
        font-size: 16px;
        line-height: 24px;
        color: #c0ccda;
        position: relative;
        top: -7px;
        left: 10px;
      }
      .connect-your-favourite-plugin {
        text-align: center;
        position: relative;
        top: 15%;
        font-family: 'Publico';
		font-weight: 600;
        font-style: normal;
        font-size: 20px;
        line-height: 24px;
        color: #1f2d3d;
      }
      .plugins {
        position: relative;
        top: 20%;
        display: flex;
        width: 90%;
        left: 10%;
		margin-left:-5%;
        justify-content: space-between;
      }
      .plugin-item {
        background: #f9fafc;
        width: 29%;
        border: 1px solid #eff2f7;
        border-radius: 8px;
        padding: 10px 0px 3px 10px;
		margin:4% 0% 5% 0%;
      }
	  .plugin-item:hover{
		cursor:pointer;
	  }
      .plugin-item-text {
        font-family: "Roboto";
        font-style: normal;
        font-weight: 400;
        font-size: 15px;
        line-height: 24px;
        text-decoration-line: underline;
        color: #005494;
        position: relative;
        left: 5px;
        bottom: 15px;
      }
      .copyright {
        font-family: "Roboto";
        font-style: normal;
        font-weight: 400;
        font-size: 15px;
        line-height: 24px;
        color: #c0ccda;
        text-align: center;
		padding: 2% 0%;
      }
      .modal {
        display: none; /* Hidden by default */
        position: fixed; /* Stay in place */
        z-index: 1; /* Sit on top */
        left: 0;
        top: 0;
        width: 100%; /* Full width */
        height: 100%; /* Full height */
        overflow: auto; /* Enable scroll if needed */
        background-color: rgb(0, 0, 0); /* Fallback color */
        background-color: rgba(0, 0, 0, 0.4); /* Black w/ opacity */
      }

      /* Modal Content/Box */
      .modal-content {
        background-color: #fefefe;
        margin: 5% 0% 95% 15%; /* 15% from the top and centered */
        border: 1px solid #888;
        width: 80%; /* Could be more or less, depending on screen size */
		border-radius: 5px;
      }

      /* The Close Button */
      .close {
        color: #aaa;
		display:flex;
		justify-content:flex-end;
        font-size: 28px;
        font-weight: bold;
		position:relative;
		left:97%;
		bottom:10px;
		height:10px;
		width:10px;
      }
	  button {
		outline: 0;
  		border: none;
		background:#f9fafc;
		cursor:pointer;
	 }
      .close:hover,
      .close:focus {
		
        color: black;
        text-decoration: none;
        cursor: pointer;
      }
	  .modal-heading{
		background:lightgray;
		width:100%;
		height:50px;
		border-radius:5px 5px 0px 0px;
	  }
	  .modalContent embed{
		height: 80vh;
		width:100%;
	  }
	  #pluginName{
		font-family: "Roboto";
        font-style: normal;
        font-weight: 500;
        font-size: 24px;
        line-height: 24px;
        color: black;
        text-shadow: 0px 1px 1px rgba(0, 0, 0, 0.25);
		text-align:center;
		position:relative;
		top:12px;
	  }
	  .success-message{
		width: 59%;
		position: relative;
		margin: auto;
		top:10%;
		display:flex;
		padding:3.5% 5% 3.5% 5%;
		background-color:#F0FAFF;
		border-radius: 5px 5px 0px 0px;
	  }
	  .ellipse-svg{
		position: relative;
		top:45%;
		left:-2%;
	  }
	  .success-text{
		font-family: 'Roboto';
		font-style: normal;
		font-weight: 400;
		font-size: 18px;
		line-height: 24px;
		color: #005494;
		width:50%;
		position: relative;
		top:20%;
		left: 2%;
	  }
	  .bold{
		font-weight: 700;
	  }
	  .go-to-dash{
		position: absolute;
		right: 19%;
		margin-top:10px;
		width:45%;
		bottom: 21%;
		text-align: right;
	  }
	  .settings-button{
		position:relative;
		right:17%;
		margin-top: 0.6%;
	  }
	  .settings-svg{
		position: relative;
		top:3px;
	  }
	  .link-to-sib{
		margin-top:30%;
	  }
	  .sib-link-button {
        background: #EFF2F7;
        border-radius: 97px;
        position: relative;
        width: 40%;
        padding: 15px 30px;
		font-style: medium;
        font-family: "Roboto";
        font-weight: 400;
        font-size: 15px;
        line-height: 20px;
        color: #005494;
        text-decoration: none;
      }
      .sib-link-button:hover,
      .sib-link-button:visited {
        color: #005494;
      }
	  .sib-link-button:hover{
		background-color: #E0E6EF;
	  }
	  .plus-icon{
		float:right;
		position: relative;
		left:15%;
	  }
	  .top-heading{
		display:flex;
		justify-content:space-between;
		flex-direction:row;
		height:20%;
		padding:3%;
	  }
	  a:active a:visited{
		text-decoration:none;
		border:1px solid white;
	  }
	  @media only screen and (max-width: 1300px) and (min-width: 901px){
		.sib-heading{
			width: 50%;
			left: 0%;
			margin: auto;
		}
		.top-heading{
		display:flex;
		justify-content:space-between;
		flex-direction:column;
		margin: 5% 0%;
		margin: auto;
		position: relative;
		height:40%;
	  }
	  .settings-button{
		position:relative;
		margin:5% auto;
		width: 50%;
		right: 0%;
		left: 0%;
		text-align: center;
	  }
	  .link-to-sib{
		margin-top:15%;
	  }
	  .features{
			display:flex;
			flex-direction:column;
			justify-content:space-between;
			width:100%;
		}
		.success-message{
			flex-direction:column;
			justify-content:"space-between";
		}
		.success-text{
		width:100%;
		left:10%;
	  	}
		.ellipse-svg{
		position:relative;
		left:40%;
		margin:4%;
	  }
	   .go-to-dash{
		position: relative;
		margin:auto;
		width:65%;
		padding: 5% 0%;
	  }
	  .email_marketing {
        width: 80%;
        height: 50%;
		margin: 5% 0%;
      }
	  .hr {
		margin:10% 0%;
      }
	  .plugins{
		display:flex;
		justify-content:space-between;
		flex-direction: column;
	  }
	  .plugin-item {
        background: #f9fafc;
        width: 90%;
        border: 1px solid #eff2f7;
        border-radius: 8px;
        padding: 5% 3%;
		margin:5% 0%;
		text-align: center;
      }
	  .plus-icon{
		position: relative;
		left:5%;
	  }
	  }
	  @media only screen and (max-width: 900px) and (min-width:600px) {
		.top-heading{
		display:flex;
		justify-content:space-between;
		flex-direction:column;
		margin: 5% 0%;
		left:10%;
		position: relative;
		height:40%;
	  }
	  .settings-button{
		position:relative;
		margin:5% 0%;
		width:40%;
		left:22%;
	  }
	  .features{
			display:flex;
			flex-direction:column;
			justify-content:space-between;
			width:100%;
		}
		.success-message{
			flex-direction:column;
			justify-content:"space-between";

		}
		.success-text{
		width:100%;
		left:2%;
	  	}
		.ellipse-svg{
		position:relative;
		left:40%;
		margin:4%;
	  }
	  .go-to-dash{
		position: relative;
		margin:auto;
		width:100%;
		padding: 7% 0%;
		right: 0%;
		text-align: center;
	  }
	  .email_marketing {
        width: 80%;
        height: 50%;
		margin: 5% 0%;
      }
	  .hr {
		margin:10% 0%;
      }
	  .plugins{
		display:flex;
		justify-content:space-between;
		flex-direction: column;
	  }
	  .plugin-item {
        background: #f9fafc;
        width: 90%;
        border: 1px solid #eff2f7;
        border-radius: 8px;
        padding: 5% 3%;
		margin:5% 0%;
		text-align: center;
      }
	  .plus-icon{
		position: relative;
		left:5%;
	  }
	  }
	  @media only screen and (max-width: 599px) and (min-width:401px){
		.top-heading{
		display:flex;
		justify-content:space-between;
		flex-direction:column;
		margin: 5% 0%;
		left:0%;
		position: relative;
		height:40%;
	  }
	  .top-heading{
			text-align: center;
		}
		.sib-heading{
		margin:auto;
		position: relative;
		width:100%;
		left:0;
	  }
	  .connect-button {
		position:relative;
		right:24%;
	  }
	  .success-message .connect-button {
        background: #0b996e;
        border-radius: 97px;
        position: relative;
        top: 10%;
		left:10%;
		width: 10%;
        padding: 20px 10px 20px 10px;
        font-family: "Roboto";
        font-style: normal;
        font-weight: 500;
        font-size: 16px;
        line-height: 20px;
        color: #fff;
        text-decoration: none;
		text-align:center;
      }
	  .settings-button{
		position:relative;
		margin:10% 0%;
		left:25%;
	  }
	  .link-to-sib{
		margin-top:20%;
	  }
	  .features{
			display:flex;
			flex-direction:column;
			justify-content:space-between;
			width:100%;
		}
		.success-message{
			flex-direction:column;
			justify-content:"space-between";
		}
		.success-text{
		width:100%;
		left:5%;
	  	}
		.ellipse-svg{
		position:relative;
		left:40%;
		margin:4%;
	  }
	  .go-to-dash{
		position: relative;
		margin:auto;
		width:100%;
		padding: 10% 0%;
		right: 0%;
	  }
	  .email_marketing {
        width: 80%;
        height: 50%;
		margin: 5% 0%;
      }
	  .hr {
		margin:10% 0%;
      }
	  .plugins{
		display:flex;
		justify-content:space-between;
		flex-direction: column;
	  }
	  .plugin-item {
        background: #f9fafc;
        width: 90%;
        border: 1px solid #eff2f7;
        border-radius: 8px;
        padding: 5% 3%;
		margin:5% 0%;
		text-align: center;
      }
	  .plus-icon{
		position: relative;
		left:5%;
	  }
	  }

	  @media only screen and (max-width: 400px) {
		
		.top-heading{
		display:flex;
		justify-content:space-between;
		flex-direction:column;
		margin: 5% 0%;
		left:0%;
		position: relative;
		height:40%;
	  }
	  .settings-button{
		position:relative;
		margin:10% 0%;
		left:25%;
	  }
	  .link-to-sib{
		margin-top:20%;
	  }
	  .features{
			display:flex;
			flex-direction:column;
			justify-content:space-between;
			width:100%;
		}
		.success-message{
			flex-direction:column;
			justify-content:"space-between";
		}
		.success-text{
		width:100%;
		left:5%;
	  	}
		.ellipse-svg{
		position:relative;
		left:40%;
		margin:4%;
	  }
	  .go-to-dash{
		position: relative;
		margin:auto;
		width:65%;
		padding: 10% 0%;
		right: 0%;
		text-align: center;
	  }
	  .connect-button{
		font-size:13px;
	  }
	  .sib-link-button{
		font-size:13px;
	  }
	  .email_marketing {
        width: 80%;
        height: 50%;
		margin: 5% 0%;
      }
	  .hr {
		margin:10% 0%;
      }
	  .plugins{
		display:flex;
		justify-content:space-between;
		flex-direction: column;
	  }
	  .plugin-item {
        background: #f9fafc;
        width: 90%;
        border: 1px solid #eff2f7;
        border-radius: 8px;
        padding: 5% 3%;
		margin:5% 0%;
		text-align: center;
      }
	  .plus-icon{
		position: relative;
		left:5%;
	  }
	  }
    </style>
	
  </head>

  <body>
    <div class="main">
      <div class="rect-top">
		<div class="top-heading">
		<div class="sib-heading">
          <span class="sib-logo">
				  <svg
					width="34"
					height="36"
					viewBox="0 0 34 36"
					fill="none"
					xmlns="http://www.w3.org/2000/svg"
				  >
				  	<circle cx="16" cy="16" r="16" fill="#0B996E"/>
					<path fill="#fff" d="M21.002 14.54c.99-.97 1.453-2.089 1.453-3.45 0-2.814-2.07-4.69-5.19-4.69H9.6v20h6.18c4.698 0 8.22-2.874 8.22-6.686 0-2.089-1.081-3.964-2.998-5.174Zm-8.62-5.538h4.573c1.545 0 2.565.877 2.565 2.208 0 1.513-1.329 2.663-4.048 3.54-1.854.574-2.688 1.059-2.997 1.634l-.094.001V9.002Zm3.151 14.796h-3.152v-3.085c0-1.362 1.175-2.693 2.813-3.208 1.453-.484 2.657-.969 3.677-1.482 1.36.787 2.194 2.148 2.194 3.57 0 2.42-2.35 4.205-5.532 4.205Z"/>
				  </svg>
				</span>
				<span class="sib-text">
				  <svg
					width="90"
					height="28"
					viewBox="0 0 90 28"
					fill="none"
					xmlns="http://www.w3.org/2000/svg"
				  >
					<path fill="#0B996E" d="M73.825 19.012c0-4.037 2.55-6.877 6.175-6.877 3.626 0 6.216 2.838 6.216 6.877s-2.59 6.715-6.216 6.715c-3.626 0-6.175-2.799-6.175-6.715Zm-3.785 0c0 5.957 4.144 10.155 9.96 10.155 5.816 0 10-4.198 10-10.155 0-5.957-4.143-10.314-10-10.314s-9.96 4.278-9.96 10.314ZM50.717 8.937l7.81 19.989h3.665l7.81-19.989h-3.945L60.399 24.37h-.08L54.662 8.937h-3.945Zm-15.18 9.354c.239-3.678 2.67-6.156 5.977-6.156 2.867 0 5.02 1.84 5.338 4.598h-6.614c-2.35 0-3.626.28-4.58 1.56h-.12v-.002Zm-3.784.6c0 5.957 4.183 10.274 9.96 10.274 3.904 0 7.33-1.998 8.804-5.158l-3.187-1.6c-1.115 2.08-3.267 3.319-5.618 3.319-2.83 0-5.379-2.16-5.379-4.238 0-1.08.718-1.56 1.753-1.56h12.63v-1.079c0-5.997-3.825-10.155-9.323-10.155-5.497 0-9.641 4.279-9.641 10.195M20.916 28.924h3.586V16.653c0-2.639 1.632-4.518 3.905-4.518.956 0 1.951.32 2.43.758.36-.96.917-1.918 1.753-2.878-.957-.799-2.59-1.32-4.184-1.32-4.382 0-7.49 3.279-7.49 7.956v12.274-.001Zm-17.33-13.23V5.937h5.896c1.992 0 3.307 1.16 3.307 2.919 0 1.998-1.713 3.518-5.218 4.677-2.39.759-3.466 1.399-3.865 2.16h-.12Zm0 9.794v-4.077c0-1.799 1.514-3.558 3.626-4.238 1.873-.64 3.425-1.28 4.74-1.958 1.754 1.04 2.829 2.837 2.829 4.717 0 3.198-3.028 5.556-7.132 5.556H3.586ZM0 28.926h7.968c6.057 0 10.597-3.798 10.597-8.835 0-2.759-1.393-5.237-3.864-6.836 1.275-1.28 1.873-2.76 1.873-4.559 0-3.717-2.67-6.196-6.693-6.196H0v26.426Z"/>
				</svg>
          </span>
          <span class="for-woocommerce"> <?php echo __('for WooCommerce', SENDINBLUE_WC_TEXTDOMAIN) ?> </span>
        </div>
		<div class="settings-button">
			<a
				href=<?php echo $settingsUrl ?>
				target="_blank"
				class="connect-button"
				style="background-color: #006a43; padding: 15px 22px; font-size: 16px;"
				onmouseover="this.style['background-color']='#0b996e';" onmouseout="this.style['background-color']='#006a43';"
			  >
			  <span class="settings-svg">
				<svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M8.99984 11C10.1044 11 10.9998 10.1046 10.9998 9.00002C10.9998 7.89545 10.1044 7.00002 8.99984 7.00002C7.89527 7.00002 6.99984 7.89545 6.99984 9.00002C6.99984 10.1046 7.89527 11 8.99984 11Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
					<path d="M13.9332 11C13.8444 11.2011 13.818 11.4241 13.8572 11.6404C13.8964 11.8567 13.9995 12.0562 14.1532 12.2134L14.1932 12.2534C14.3171 12.3772 14.4155 12.5242 14.4826 12.6861C14.5497 12.848 14.5842 13.0215 14.5842 13.1967C14.5842 13.3719 14.5497 13.5454 14.4826 13.7073C14.4155 13.8691 14.3171 14.0162 14.1932 14.14C14.0693 14.264 13.9223 14.3623 13.7604 14.4294C13.5986 14.4965 13.4251 14.5311 13.2498 14.5311C13.0746 14.5311 12.9011 14.4965 12.7393 14.4294C12.5774 14.3623 12.4303 14.264 12.3065 14.14L12.2665 14.1C12.1094 13.9463 11.9098 13.8432 11.6936 13.804C11.4773 13.7648 11.2542 13.7913 11.0532 13.88C10.856 13.9645 10.6878 14.1049 10.5694 14.2837C10.4509 14.4626 10.3874 14.6722 10.3865 14.8867V15C10.3865 15.3536 10.246 15.6928 9.99598 15.9428C9.74593 16.1929 9.40679 16.3334 9.05317 16.3334C8.69955 16.3334 8.36041 16.1929 8.11036 15.9428C7.86031 15.6928 7.71984 15.3536 7.71984 15V14.94C7.71468 14.7194 7.64325 14.5054 7.51484 14.3258C7.38644 14.1463 7.20699 14.0095 6.99984 13.9334C6.79876 13.8446 6.57571 13.8181 6.35945 13.8574C6.14318 13.8966 5.94362 13.9997 5.7865 14.1534L5.7465 14.1934C5.62267 14.3173 5.47562 14.4157 5.31376 14.4828C5.15189 14.5499 4.97839 14.5844 4.80317 14.5844C4.62795 14.5844 4.45445 14.5499 4.29258 14.4828C4.13072 14.4157 3.98367 14.3173 3.85984 14.1934C3.73587 14.0695 3.63752 13.9225 3.57042 13.7606C3.50333 13.5987 3.46879 13.4252 3.46879 13.25C3.46879 13.0748 3.50333 12.9013 3.57042 12.7394C3.63752 12.5776 3.73587 12.4305 3.85984 12.3067L3.89984 12.2667C4.05353 12.1096 4.15663 11.91 4.19584 11.6937C4.23505 11.4775 4.20858 11.2544 4.11984 11.0534C4.03533 10.8562 3.89501 10.688 3.71615 10.5696C3.53729 10.4511 3.32769 10.3875 3.11317 10.3867H2.99984C2.64622 10.3867 2.30708 10.2462 2.05703 9.99616C1.80698 9.74611 1.6665 9.40698 1.6665 9.05335C1.6665 8.69973 1.80698 8.36059 2.05703 8.11055C2.30708 7.8605 2.64622 7.72002 2.99984 7.72002H3.05984C3.2805 7.71486 3.49451 7.64343 3.67404 7.51503C3.85357 7.38662 3.99031 7.20718 4.0665 7.00002C4.15525 6.79894 4.18172 6.57589 4.14251 6.35963C4.10329 6.14336 4.0002 5.94381 3.8465 5.78669L3.8065 5.74669C3.68254 5.62286 3.58419 5.4758 3.51709 5.31394C3.44999 5.15208 3.41546 4.97857 3.41546 4.80335C3.41546 4.62813 3.44999 4.45463 3.51709 4.29277C3.58419 4.1309 3.68254 3.98385 3.8065 3.86002C3.93033 3.73605 4.07739 3.63771 4.23925 3.57061C4.40111 3.50351 4.57462 3.46897 4.74984 3.46897C4.92506 3.46897 5.09856 3.50351 5.26042 3.57061C5.42229 3.63771 5.56934 3.73605 5.69317 3.86002L5.73317 3.90002C5.89029 4.05371 6.08985 4.15681 6.30611 4.19602C6.52237 4.23524 6.74543 4.20876 6.9465 4.12002H6.99984C7.19702 4.03551 7.36518 3.89519 7.48363 3.71633C7.60208 3.53747 7.66565 3.32788 7.6665 3.11335V3.00002C7.6665 2.6464 7.80698 2.30726 8.05703 2.05721C8.30708 1.80716 8.64622 1.66669 8.99984 1.66669C9.35346 1.66669 9.6926 1.80716 9.94265 2.05721C10.1927 2.30726 10.3332 2.6464 10.3332 3.00002V3.06002C10.334 3.27454 10.3976 3.48414 10.516 3.663C10.6345 3.84186 10.8027 3.98218 10.9998 4.06669C11.2009 4.15543 11.424 4.1819 11.6402 4.14269C11.8565 4.10348 12.0561 4.00038 12.2132 3.84669L12.2532 3.80669C12.377 3.68272 12.5241 3.58437 12.6859 3.51727C12.8478 3.45018 13.0213 3.41564 13.1965 3.41564C13.3717 3.41564 13.5452 3.45018 13.7071 3.51727C13.869 3.58437 14.016 3.68272 14.1398 3.80669C14.2638 3.93052 14.3622 4.07757 14.4293 4.23943C14.4963 4.4013 14.5309 4.5748 14.5309 4.75002C14.5309 4.92524 14.4963 5.09874 14.4293 5.26061C14.3622 5.42247 14.2638 5.56952 14.1398 5.69335L14.0998 5.73335C13.9461 5.89047 13.843 6.09003 13.8038 6.30629C13.7646 6.52256 13.7911 6.74561 13.8798 6.94669V7.00002C13.9643 7.1972 14.1047 7.36537 14.2835 7.48382C14.4624 7.60227 14.672 7.66583 14.8865 7.66669H14.9998C15.3535 7.66669 15.6926 7.80716 15.9426 8.05721C16.1927 8.30726 16.3332 8.6464 16.3332 9.00002C16.3332 9.35364 16.1927 9.69278 15.9426 9.94283C15.6926 10.1929 15.3535 10.3334 14.9998 10.3334H14.9398C14.7253 10.3342 14.5157 10.3978 14.3369 10.5162C14.158 10.6347 14.0177 10.8028 13.9332 11Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
				  </svg>
			  </span>
			  &nbsp; <?php echo __('Settings', SENDINBLUE_WC_TEXTDOMAIN) ?>
			  	</a>
		</div>
		</div>

        
		<div class="success-message">
			<div class="ellipse-svg">
				<svg width="42" height="42" viewBox="0 0 42 42" fill="none" xmlns="http://www.w3.org/2000/svg">
					<circle cx="21" cy="21" r="21" fill="#17A810"/>
					<path d="M26.3332 17L18.9998 24.3333L15.6665 21" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>		
			</div>
			<div class="success-text">
				<span class="bold"><?php echo __('You’re all set!', SENDINBLUE_WC_TEXTDOMAIN) ?></span> 
				<?php echo __('The connection between Woocommerce and Brevo works!', SENDINBLUE_WC_TEXTDOMAIN) ?>
			</div>
		</div>
		<div class="go-to-dash">
				<a
				href=<?php echo $dashboardUrl ?>
				target="_blank"
				class="connect-button"
				id="goToDashButton"
			  >
			  <?php echo __('Go to dashboard', SENDINBLUE_WC_TEXTDOMAIN) ?> &nbsp;
				<svg
				  width="14"
				  height="14"
				  viewBox="0 0 14 14"
				  fill="none"
				  xmlns="http://www.w3.org/2000/svg"
				>
				  <path
					d="M11 7.66667V11.6667C11 12.0203 10.8595 12.3594 10.6095 12.6095C10.3594 12.8595 10.0203 13 9.66667 13H2.33333C1.97971 13 1.64057 12.8595 1.39052 12.6095C1.14048 12.3594 1 12.0203 1 11.6667V4.33333C1 3.97971 1.14048 3.64057 1.39052 3.39052C1.64057 3.14048 1.97971 3 2.33333 3H6.33333M9 1H13M13 1V5M13 1L5.66667 8.33333"
					stroke="white"
					stroke-width="2"
					stroke-linecap="round"
					stroke-linejoin="round"
				  />
				</svg>
			  	</a>
			</div>
      </div>

      <div class="rect-bottom">
        <div class="bottom-overlay">
          <div class="features">
            <div class="email_marketing">
              <div class="email_marketing_img_overlay">
			  <svg width="100" height="100" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
<rect width="100" height="100" fill="url(#pattern0)"/>
<defs>
<pattern id="pattern0" patternContentUnits="objectBoundingBox" width="1" height="1">
<use xlink:href="#image0_40_342" transform="scale(0.002)"/>
</pattern>
<image id="image0_40_342" width="500" height="500" xlink:href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAfQAAAH0CAYAAADL1t+KAAAACXBIWXMAAAsTAAALEwEAmpwYAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAqNSURBVHgB7d0xiKRnGcDx5zsnYlT0tFA7TxAkcOAJNlaeIEQiooK1Jgp2GpeoYBHvYmNhZCWdFnpaWFhpiiAouKWF4EEiISC4V6bKBhKS4nLjrFZJ8DghO7vzv98P5mkHXr73+8+8A/PNAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACwG5bZktUD31sPhN186idb2085++vzm3lpdtP12VuO5iTsry/PbjrcrMnhnJT99fG1cn52xd5yMFuwGoDTd2neNn+ZXfTafGYzD+Yk7OqarOexzbw6J2U1+5v3uDy7Yysf9s8NALDzBB0AAgQdAAIEHQACBB0AAgQdAAIEHQACBB0AAgQdAAIEHQACBB0AAgQdAAIEHQACBB0AAgQdAAIEHQACBB0AAgQdAAIEHQACBB0AAgQdAAIEHQACBB0AAgQdAAIEHQACBB0AAgQdAAIEHQACBB0AAgQdAAIEHQACBB0AAlYDvCXWz/79wpymV+do+cQnjga4Kwk6vFXO3fOvOU3vmIc289oAdyVH7gAQIOgAECDoABAg6AAQIOgAECDoABAg6AAQIOgAECDoABAg6AAQ4K9fb+PDH3zf/POXPxi258bzL8xHv/7jAeD/I+jAWXB9XvvPf9HvosM5Kbu7JtfnJN2cxzbz18PrCDpw+vaW46fEXRteb2+5NrzZ3nIwvInf0AEgQNABIEDQASBA0AEgQNABIEDQASBA0AEgQNABIEDQASBA0AEgQNABIEDQASBA0AEgQNABIEDQASBA0AEgQNABIEDQASBA0AEgQNABIGA1AKdtf31pczfan110c/Zmb7k+bM8T6/1Z5tLsim8tn5ktEHTgLDg/67k8u+n8sF3HMd/d6+XEOHIHgABBB4AAQQeAAEEHgABBB4AAQQeAAEEHgABBB4AAQQeAAEEHgABBB4AAQQeAAEEHgABBB4AAQQeAAEEHgABBB4AAQQeAAEEHgABBB4AAQQeAAEEHgABBB4AAQQeAAEEHgABBB4AAQQeAAEEHgABBB4AAQQeAAEEHgABBB4AAQQeAAEEHgABBB4AAQQeAAEEHgABBB4AAQQeAAEEHgABBB4AAQQeAAEEHgABBB4CA1QCcvqPN63DgTtyaG7O4Xt5I0IHTt7dc38yPDNyJh5cHhzdx5A4AAYIOAAGCDgABgg4AAYIOAAGCDgABgg4AAYIOAAGCDgABgg4AAYIOAAGCDgABgg4AAYIOAAGCDgABgg4AAYIOAAGCDgABgg4AAYIOAAGrATht++sLm68XD84uujXXZm85nJOwv760WZcvzS66NT/brMvRnIQn1g/Oei7Mrnh4uTpbIOjAWXBhlrkyu+lg8zqck/C2ubSZu7ku98yvNvNkgr7M1zbz8uyOq7MFgn4bN55/Ye75/PcHAM46v6EDQICgA0CAoANAgKADQICgA0CAoANAgKADQICgA0CAoANAgKADQICgA0CAoANAgKADQICgA0CAoANAgKADQICgA0CAoANAgKADQICgA0CAoANAgKADQICgA0CAoANAgKADQICgA0CAoANAgKADQICgA0CAoANAgKADQICgA0CAoANAgKADQICgA0CAoANAgKADQICgA0CAoANAgKADQICgA0CAoANAgKADQICgA0DAarZkvZ7DOUXvf+fbz7/n3refH+Ds2VsOZn/9kdlNR3NSXpvfb+bB7KJvLzfmpNycL2+m+/kbLHOXWD/79NU5t1wZqLo1Dy33Xbw2wF3JkTsABAg6AAQIOgAECDoABAg6AAQIOgAECDoABAg6AAQIOgAECDoABAg6AAQIOgAECDoABAg6AAQIOgAECDoABAg6AAQIOgAECDoABAg6AAQIOgAECDoABAg6AAQIOgAECDoABAg6AAQIOgAECDoABAg6AAQIOgAECDoABAg6AAQIOgAErOZusSxHm3E4ULU+vsYBAAAAAAAAAAAAAAAAAAAAAAAAAAAAeIsssyWrB757dSDs5lOPX51d9LnvXFidWz04UHVrDm7+8fGDidviw1mWKwNtV2cHrWZ1wf4k7dz6eB5MnMenAkCAoANAgKADQICgA0CAoANAgKADQICgA0CAoANAgKADQICgA0CAoANAgKADQICgA0CAoANAgKADQICgA0CAoANAgKADQICgA0CAoANAgKADQICgA0CAoANAgKADQICgA0CAoANAgKADQICgA0CAoANAgKADQMBqgLfE+rln1nOabs1Dy30Xrw3wOo/ef+nKD/efuTKnZPnYxWW2wDd0AAgQdAAIEHQACBB0AAgQdAAIEHQACBB0AAgQdAAIEHQACBB0AAgQdAAIEHQACBB0AAjwtLXbOP+ue+en3/zCsD1HL786j/ziyYE7cbw/j/cp23O8P4/3KWePoN/Ge9/9jvnqZz85bM+N518QdO7YFz91cT78gfcN2/Oj3/5J0M8oR+4AECDoABAg6AAQIOgAECDoABAg6AAQIOgAECDoABAg6AAQIOgAECDoABAg6AAQIOgAECDoABAg6AAQIOgAECDoABAg6AAQIOgAECDoABAg6AAQIOgAECDoABAg6AAQIOgAECDoABAg6AAQIOgAECDoABAg6AAQIOgAECDoABAg6AAQIOgAECDoABAg6AAQIOgAECDoABAg6AAQIOgAECDoABAg6AAQIOgAECDoABAg6AAQIOgAECDoABAg6AAQIOgAECDoABAg6AAQIOgAECDoABAg6AAQIOgAECDoABAg6AAQIOgAECDoABAg6AAQIOgAECDoABAg6AAQIOgAECDoABAg6AAQIOgAECDoABAg6AAQIOgAECDoABAg6AAQIOgAECDoABAg6AAQIOgAECDoABAg6AAQIOgAECDoABAg6AAQIOgAECDoABAg6AAQIOgAECDoABAg6AAQIOgAECDoABAg6AAQIOgAELAa/qcXX3p1vrH/u2F7XnzplYE79cjPn5z3vvveYXvs0bNL0G/j6OVX5jd//tsAZ9Mf/vqPAf7LkTsABAg6AAQIOgAECDoABAg6AAQIOgAECDoABAg6AAQIOgAECDoABAg6AAQIOgAECDoABAg6AAQIOgAECDoABAg6AAQIOgAECDoABAg6AAQIOgAECDoABAg6AAQIOgAECDoABAg6AAQIOgAECDoABAg6AAQIOgAECDoABAg6AAQIOgAECDoABAg6AAQIOgAECDoABAg6AAQIOgAECDoABAg6AAQIOgAECDoABAg6AASsZmvWj80p+srHL3z6vg+dvzzA69ycm4erWZ3a/nz0/ktXBk7Qpz/6obkbLHOXWD/79NU5t7hx0HVrHlruu3htdsz6uWfWA2HLxy5upbWO3AEgQNABIEDQASBA0AEgQNABIEDQASBA0AEgQNABIEDQASBA0AEgQNABIEDQASBA0AEgQNABIEDQASBA0AEgQNABIEDQASBA0AEgQNABIEDQASBA0AEgQNABIEDQASBA0AEgQNABIEDQASBA0AEgQNABIEDQAQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAALbm30Z6xq6EiXtiAAAAAElFTkSuQmCC"/>
</defs>
</svg>

              </div>
              <div class="email_marketing_content">
                <div class="email_marketing_heading">  <?php echo __('Email Marketing', SENDINBLUE_WC_TEXTDOMAIN) ?></div>
                <div class="email_marketing_subheading">
				<?php echo __('Sell more with sleek email messages that you can design in no time', SENDINBLUE_WC_TEXTDOMAIN) ?>
                </div>
              </div>
			  <div class="link-to-sib">
				
			  <a
				href=<?php echo $emailMarketingUrl ?>
				target="_blank"
				class="sib-link-button"
			  >
			  <?php echo __('See my campaigns', SENDINBLUE_WC_TEXTDOMAIN) ?> &nbsp;
			  <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path d="M11 7.66667V11.6667C11 12.0203 10.8595 12.3594 10.6095 12.6095C10.3594 12.8595 10.0203 13 9.66667 13H2.33333C1.97971 13 1.64057 12.8595 1.39052 12.6095C1.14048 12.3594 1 12.0203 1 11.6667V4.33333C1 3.97971 1.14048 3.64057 1.39052 3.39052C1.64057 3.14048 1.97971 3 2.33333 3H6.33333M9 1H13M13 1V5M13 1L5.66667 8.33333" stroke="#0092FF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
			  </svg>
			  	</a>
			  </div>
            </div>
            <div class="email_marketing">
              <div class="email_marketing_img_overlay" style="padding: 14px 40px;">
			  <svg width="102" height="102" viewBox="0 0 102 102" fill="none" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
<rect width="102" height="102" fill="url(#pattern1)"/>
<defs>
<pattern id="pattern1" patternContentUnits="objectBoundingBox" width="1" height="1">
<use xlink:href="#image0_40_358" transform="scale(0.002)"/>
</pattern>
<image id="image0_40_358" width="500" height="500" xlink:href="data:image/png;base64,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"/>
</defs>
</svg>
              </div>
              <div class="email_marketing_content">
                <div class="email_marketing_heading"><?php echo __('Automation', SENDINBLUE_WC_TEXTDOMAIN) ?></div>
                <div class="email_marketing_subheading">
				<?php echo __('Save time and boost performance by automating your marketing messages', SENDINBLUE_WC_TEXTDOMAIN) ?>
                </div>
              </div>
			  <div class="link-to-sib">
				<a
				  href=<?php echo $automationUrl ?>
				  target="_blank"
				  class="sib-link-button"
				>
				<?php echo __('See my workflows', SENDINBLUE_WC_TEXTDOMAIN) ?> &nbsp;
				<svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
				  <path d="M11 7.66667V11.6667C11 12.0203 10.8595 12.3594 10.6095 12.6095C10.3594 12.8595 10.0203 13 9.66667 13H2.33333C1.97971 13 1.64057 12.8595 1.39052 12.6095C1.14048 12.3594 1 12.0203 1 11.6667V4.33333C1 3.97971 1.14048 3.64057 1.39052 3.39052C1.64057 3.14048 1.97971 3 2.33333 3H6.33333M9 1H13M13 1V5M13 1L5.66667 8.33333" stroke="#0092FF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
					</a>
			  </div>
            </div>
            <div class="email_marketing">
              <div class="email_marketing_img_overlay" style="padding: 12px 40px;">
			  <svg width="106" height="106" viewBox="0 0 106 106" fill="none" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
<rect width="106" height="106" fill="url(#pattern2)"/>
<defs>
<pattern id="pattern2" patternContentUnits="objectBoundingBox" width="1" height="1">
<use xlink:href="#image0_40_357" transform="scale(0.002)"/>
</pattern>
<image id="image0_40_357" width="500" height="500" xlink:href="data:image/png;base64,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"/>
</defs>
</svg>

              </div>
              <div class="email_marketing_content">
                <div class="email_marketing_heading"><?php echo __('Conversations', SENDINBLUE_WC_TEXTDOMAIN) ?></div>
                <div class="email_marketing_subheading">
				<?php echo __('Be there for customers instantly when they have a question
                  while browsing your site', SENDINBLUE_WC_TEXTDOMAIN) ?>
                </div>
              </div>
			  <div class="link-to-sib">
				<a
				  href=<?php echo $conversationsUrl ?>
				  target="_blank"
				  class="sib-link-button"
				>
				<?php echo __('See my conversations', SENDINBLUE_WC_TEXTDOMAIN) ?> &nbsp;
				<svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
				  <path d="M11 7.66667V11.6667C11 12.0203 10.8595 12.3594 10.6095 12.6095C10.3594 12.8595 10.0203 13 9.66667 13H2.33333C1.97971 13 1.64057 12.8595 1.39052 12.6095C1.14048 12.3594 1 12.0203 1 11.6667V4.33333C1 3.97971 1.14048 3.64057 1.39052 3.39052C1.64057 3.14048 1.97971 3 2.33333 3H6.33333M9 1H13M13 1V5M13 1L5.66667 8.33333" stroke="#0092FF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
					</a>
			  </div>
            </div>
          </div>
          <div class="hr"></div>
          <div class="connect-your-favourite-plugin">
		  <?php echo __('Connect to your favorite plugins', SENDINBLUE_WC_TEXTDOMAIN) ?>
          </div>
          <div class="plugins">
            <span class="plugin-item" id="wp_forms"  onmouseover="this.style['background-color'] = '#EFF2F7'" onmouseout="this.style['background-color'] = '#f9fafc'">
              <span class="plugin-item-img">
                <svg
                  width="53"
                  height="41"
                  viewBox="0 0 53 41"
                  fill="none"
                  xmlns="http://www.w3.org/2000/svg"
                >
                  <path
                    d="M5.95947 25.3346C8.88172 25.7276 11.4394 25.067 13.5186 23.1992L16.9991 23.3814L15.1022 28.9677C12.9945 32.3103 9.93556 33.0221 6.80254 32.8114L5.95947 25.3346Z"
                    fill="#7EAABA"
                  />
                  <path
                    d="M7.25244 26.5987L7.82208 31.7237C10.431 31.7237 12.5102 30.8524 14.0881 28.4836L15.461 24.4974L13.9343 24.4177C12.0667 25.9609 9.68969 26.7509 7.26953 26.6329L7.25244 26.5987Z"
                    fill="#D3E8EF"
                  />
                  <path
                    d="M7.25244 26.5987L7.59423 29.6224C11.1773 29.201 13.9685 27.7262 15.4154 24.4633L13.8887 24.3835C12.0323 25.9268 9.66401 26.7173 7.25244 26.5987Z"
                    fill="white"
                  />
                  <path
                    d="M6.80219 20.9613C7.9755 20.9602 9.12279 21.307 10.0988 21.958C11.0749 22.6089 11.8358 23.5347 12.2854 24.6181C12.7349 25.7015 12.8528 26.8939 12.6242 28.0443C12.3955 29.1947 11.8306 30.2515 11.001 31.0809C10.1713 31.9103 9.11419 32.475 7.96336 32.7035C6.81253 32.9321 5.61975 32.8142 4.53596 32.3648C3.45217 31.9154 2.52609 31.1548 1.87494 30.179C1.22378 29.2033 0.876806 28.0564 0.877932 26.8835C0.880944 25.3138 1.50607 23.8092 2.61643 22.6992C3.72679 21.5892 5.2319 20.9643 6.80219 20.9613"
                    fill="#7EAABA"
                  />
                  <path
                    d="M6.80255 22.1173C5.86382 22.1162 4.94585 22.3934 4.16477 22.914C3.38369 23.4345 2.77462 24.1749 2.4146 25.0416C2.05458 25.9082 1.9598 26.8622 2.14224 27.7827C2.32469 28.7032 2.77617 29.549 3.43955 30.2129C4.10294 30.8769 4.94842 31.3292 5.86904 31.5127C6.78965 31.6962 7.74403 31.6026 8.61141 31.2437C9.4788 30.8849 10.2202 30.2769 10.7419 29.4967C11.2635 28.7165 11.542 27.7992 11.542 26.8608C11.5427 26.2381 11.4207 25.6214 11.1828 25.046C10.945 24.4705 10.596 23.9475 10.1558 23.507C9.71567 23.0664 9.19295 22.717 8.61756 22.4785C8.04218 22.24 7.42542 22.1173 6.80255 22.1173"
                    fill="#D3E8EF"
                  />
                  <path
                    d="M6.80259 22.1174C6.03725 22.1135 5.2825 22.296 4.6036 22.6492C3.92471 23.0025 3.34212 23.5157 2.90625 24.1446C2.98688 25.3482 3.52055 26.4767 4.39987 27.3029C5.27919 28.1291 6.43891 28.5917 7.64565 28.5976C8.41098 28.6016 9.16574 28.419 9.84464 28.0658C10.5235 27.7126 11.1061 27.1993 11.542 26.5704C11.4666 25.3648 10.9346 24.2333 10.0541 23.406C9.17365 22.5787 8.01093 22.1179 6.80259 22.1174"
                    fill="white"
                  />
                  <path
                    d="M6.19865 21.8324C7.07526 21.8324 7.93213 22.0927 8.6606 22.5801C9.38907 23.0676 9.95635 23.7603 10.2905 24.5704C10.6246 25.3806 10.7106 26.2717 10.5375 27.1307C10.3644 27.9898 9.94002 28.7781 9.31817 29.3957C8.69632 30.0134 7.905 30.4325 7.04455 30.6C6.1841 30.7675 5.29328 30.6758 4.48503 30.3365C3.67678 29.9973 2.9875 29.4258 2.5046 28.6944C2.0217 27.963 1.76691 27.1048 1.77255 26.2285C1.77708 25.0591 2.24576 23.9392 3.07565 23.1149C3.90554 22.2907 5.02878 21.8294 6.19865 21.8324Z"
                    fill="#7EAABA"
                  />
                  <path
                    d="M5.87964 20.6424C6.75939 20.6435 7.61899 20.9057 8.34952 21.3957C9.08006 21.8857 9.64863 22.5815 9.9832 23.3949C10.3178 24.2082 10.4033 25.1026 10.2289 25.9646C10.0545 26.8266 9.62799 27.6174 9.00353 28.2369C8.37906 28.8563 7.58469 29.2765 6.72108 29.4442C5.85748 29.6119 4.96351 29.5195 4.15247 29.1788C3.34143 28.8381 2.64982 28.2644 2.16529 27.5303C1.68076 26.7963 1.42512 25.935 1.43076 25.0556C1.43976 23.8809 1.91209 22.7573 2.74512 21.9288C3.57815 21.1003 4.70457 20.6339 5.87964 20.631"
                    fill="#7F3E13"
                  />
                  <path
                    d="M6.3013 22.3279C5.60278 22.3279 4.91995 22.5349 4.33916 22.9229C3.75836 23.3108 3.30568 23.8622 3.03837 24.5074C2.77106 25.1525 2.70112 25.8624 2.83739 26.5472C2.97367 27.2321 3.31004 27.8612 3.80396 28.3549C4.29789 28.8487 4.92719 29.1849 5.61229 29.3212C6.29738 29.4574 7.0075 29.3875 7.65285 29.1202C8.29819 28.853 8.84978 28.4005 9.23786 27.8199C9.62593 27.2393 9.83307 26.5567 9.83307 25.8584C9.83156 24.9225 9.45898 24.0254 8.79697 23.3636C8.13496 22.7018 7.23752 22.3294 6.3013 22.3279"
                    fill="#B85A1B"
                  />
                  <path
                    d="M3.64103 17.0833C3.64103 16.5698 3.84509 16.0774 4.2083 15.7143C4.57152 15.3512 5.06414 15.1472 5.57781 15.1472C6.09147 15.1472 6.5841 15.3512 6.94732 15.7143C7.31053 16.0774 7.51458 16.5698 7.51458 17.0833C8.5912 15.2611 11.992 16.2633 10.9324 19.1106C10.9903 19.3354 11.0341 19.5636 11.0634 19.7939C12.9091 19.8964 13.5129 22.7664 10.9837 23.6661C10.7954 24.4036 10.456 25.0939 9.98682 25.6933C7.82219 27.5611 7.13862 26.035 5.95377 24.7708C4.81449 24.7708 1.73844 24.0078 2.60998 26.9006C0.639031 25.625 -0.62557 23.0682 0.320032 19.3269C-0.995835 16.2975 2.50745 15.17 3.64103 17.0492V17.0833Z"
                    fill="#7F3E13"
                  />
                  <path
                    d="M1.51052 19.7482C1.0605 21.5648 1.03202 23.6204 2.3251 25.0953C4.14225 27.1738 7.82782 27.0713 9.25192 24.4632C10.0152 23.0396 9.96397 21.2743 9.64497 19.7254C6.96049 19.1845 4.195 19.1845 1.51052 19.7254V19.7482Z"
                    fill="#B85A1B"
                  />
                  <path
                    d="M1.51052 19.7484C1.0605 21.5649 1.03202 23.6206 2.3251 25.0955C2.73886 25.55 3.24278 25.9133 3.80475 26.1623C4.36673 26.4113 4.97443 26.5406 5.58914 26.5419V19.327C4.21929 19.3376 2.85357 19.4787 1.51052 19.7484"
                    fill="#E1762F"
                  />
                  <path
                    d="M3.29921 21.1435C3.12562 21.6403 3.07794 22.1724 3.16045 22.6921C3.24296 23.2118 3.45308 23.703 3.77201 24.1217C4.02094 24.405 4.33365 24.6252 4.68437 24.764C5.0351 24.9028 5.41381 24.9564 5.7893 24.9203C6.16479 24.8841 6.52631 24.7593 6.84409 24.5561C7.16187 24.3529 7.42681 24.0771 7.61708 23.7515C7.99099 22.9257 8.06551 21.9955 7.82784 21.1207C6.33368 20.816 4.79337 20.816 3.29921 21.1207"
                    fill="#7F3E13"
                  />
                  <path
                    d="M5.56641 18.3532C5.82588 18.3339 6.06845 18.2172 6.24547 18.0266C6.42249 17.8359 6.52087 17.5854 6.52087 17.3253C6.52087 17.0652 6.42249 16.8147 6.24547 16.6241C6.06845 16.4335 5.82588 16.3168 5.56641 16.2975V18.3532ZM9.28046 17.0833C9.48301 17.0844 9.68068 17.1455 9.84855 17.2588C10.0164 17.3721 10.147 17.5326 10.2237 17.72C10.3004 17.9074 10.3199 18.1133 10.2797 18.3118C10.2395 18.5102 10.1414 18.6923 9.99778 18.8351C9.85416 18.9779 9.67145 19.0749 9.47271 19.114C9.27397 19.1531 9.0681 19.1325 8.88107 19.0547C8.69404 18.977 8.53424 18.8456 8.42182 18.6772C8.30941 18.5087 8.24941 18.3108 8.24941 18.1083C8.25091 17.8359 8.36021 17.5752 8.5534 17.3832C8.7466 17.1911 9.00799 17.0833 9.28046 17.0833"
                    fill="#B85A1B"
                  />
                  <path
                    d="M2.06279 17.1687C2.32226 17.188 2.56483 17.3047 2.74185 17.4953C2.91887 17.686 3.01725 17.9364 3.01725 18.1966C3.01725 18.4567 2.91887 18.7071 2.74185 18.8978C2.56483 19.0884 2.32226 19.2051 2.06279 19.2244C1.79591 19.2189 1.54105 19.1124 1.34967 18.9264C1.1583 18.7404 1.04467 18.4887 1.03174 18.2222C1.03324 17.9498 1.14254 17.6891 1.33573 17.497C1.52893 17.305 1.79032 17.1972 2.06279 17.1972V17.1687ZM5.56607 18.3816V16.3259C5.29895 16.336 5.04579 16.4478 4.85837 16.6383C4.67095 16.8289 4.56343 17.0838 4.55781 17.3509C4.55853 17.4847 4.5859 17.6171 4.63833 17.7402C4.69076 17.8634 4.76719 17.9748 4.86316 18.0681C4.95914 18.1614 5.07274 18.2347 5.19733 18.2836C5.32193 18.3325 5.45502 18.3562 5.58886 18.3531L5.56607 18.3816Z"
                    fill="#E1762F"
                  />
                  <path
                    d="M10.4366 20.7791C10.6394 20.7791 10.8377 20.8392 11.0063 20.9518C11.1749 21.0644 11.3063 21.2245 11.3839 21.4118C11.4615 21.5991 11.4818 21.8052 11.4423 22.004C11.4027 22.2029 11.3051 22.3855 11.1617 22.5289C11.0183 22.6722 10.8356 22.7698 10.6367 22.8094C10.4378 22.8489 10.2316 22.8286 10.0442 22.7511C9.85689 22.6735 9.69675 22.5421 9.58408 22.3735C9.47142 22.205 9.41128 22.0068 9.41128 21.8041C9.40897 21.6688 9.43391 21.5345 9.48462 21.4091C9.53533 21.2837 9.61077 21.1698 9.70645 21.0741C9.80212 20.9785 9.91608 20.9031 10.0415 20.8524C10.167 20.8017 10.3013 20.7768 10.4366 20.7791Z"
                    fill="#B85A1B"
                  />
                  <path
                    d="M45.6802 32.8911C45.4466 29.8104 44.051 27.5952 41.311 26.4108L41.8807 24.2811L46.3068 23.0909C50.1518 24.2298 51.6272 26.7297 52.362 29.8104L45.6973 32.8911H45.6802Z"
                    fill="#7EAABA"
                  />
                  <path
                    d="M46.603 31.2057L51.0064 29.15C50.2373 26.5704 48.95 24.9134 46.2385 24.2528L42.7865 25.1753L42.627 25.7448C43.6403 26.3184 44.5199 27.101 45.2074 28.0406C45.8949 28.9802 46.3745 30.0552 46.6144 31.1943L46.603 31.2057Z"
                    fill="white"
                  />
                  <path
                    d="M41.4139 33.4149C41.0583 32.3349 41.0309 31.1737 41.3352 30.0781C41.6395 28.9825 42.2619 28.0017 43.1236 27.2595C43.9853 26.5174 45.0478 26.0472 46.1767 25.9085C47.3057 25.7697 48.4504 25.9686 49.4663 26.4799C50.4822 26.9913 51.3237 27.7922 51.8845 28.7815C52.4452 29.7707 52.7 30.9039 52.6168 32.0379C52.5335 33.1719 52.1159 34.2558 51.4166 35.1526C50.7174 36.0494 49.7679 36.7189 48.6882 37.0765C47.9701 37.3158 47.2119 37.4109 46.4569 37.3563C45.702 37.3017 44.9653 37.0983 44.2893 36.7581C43.6132 36.4178 43.0111 35.9472 42.5177 35.3735C42.0242 34.7997 41.6491 34.1341 41.4139 33.4149"
                    fill="#7EAABA"
                  />
                  <path
                    d="M42.3649 33.1019C42.6598 33.9947 43.2128 34.78 43.9541 35.3587C44.6954 35.9373 45.5916 36.2833 46.5295 36.3529C47.4675 36.4225 48.405 36.2125 49.2236 35.7496C50.0422 35.2866 50.7051 34.5915 51.1286 33.752C51.5521 32.9125 51.7171 31.9663 51.6028 31.0331C51.4884 30.0999 51.0999 29.2215 50.4863 28.509C49.8727 27.7964 49.0616 27.2817 48.1554 27.0299C47.2493 26.7781 46.2888 26.8005 45.3954 27.0943C44.8011 27.2889 44.2512 27.5988 43.777 28.0063C43.3029 28.4139 42.9139 28.9109 42.6324 29.4691C42.3508 30.0272 42.1822 30.6354 42.1364 31.2588C42.0905 31.8822 42.1681 32.5086 42.3649 33.1019"
                    fill="#D3E8EF"
                  />
                  <path
                    d="M51.582 30.9664C51.5486 30.6803 51.4876 30.398 51.3998 30.1236C51.2051 29.5302 50.8952 28.9811 50.4879 28.5076C50.0807 28.034 49.584 27.6455 49.0263 27.3641C48.4686 27.0827 47.8609 26.914 47.2379 26.8677C46.6149 26.8214 45.9889 26.8983 45.3958 27.0942C44.6619 27.3473 43.9942 27.7618 43.4419 28.3071C43.48 28.5929 43.5429 28.8749 43.6299 29.1499C44.0257 30.3465 44.88 31.3373 46.0055 31.9052C47.131 32.4731 48.4358 32.5717 49.6339 32.1793C50.378 31.9518 51.0496 31.5337 51.582 30.9664"
                    fill="white"
                  />
                  <path
                    d="M40.337 34.8898C40.0002 33.8712 39.9733 32.7755 40.2597 31.7415C40.5462 30.7076 41.1331 29.7818 41.9462 29.0815C42.7592 28.3812 43.7619 27.9378 44.8272 27.8075C45.8925 27.6772 46.9725 27.8657 47.9305 28.3494C48.8885 28.833 49.6815 29.5899 50.2089 30.5243C50.7363 31.4587 50.9746 32.5285 50.8934 33.5983C50.8123 34.6681 50.4154 35.6898 49.7531 36.534C49.0907 37.3782 48.1927 38.007 47.1727 38.3407C46.4972 38.564 45.7843 38.6519 45.0748 38.5992C44.3654 38.5466 43.6732 38.3544 43.0382 38.0338C42.4031 37.7132 41.8376 37.2705 41.3741 36.731C40.9106 36.1915 40.5581 35.5658 40.337 34.8898"
                    fill="#7F3E13"
                  />
                  <path
                    d="M41.4424 34.5481C41.1766 33.7464 41.1547 32.8838 41.3793 32.0695C41.6039 31.2553 42.065 30.5259 42.7043 29.9736C43.3436 29.4213 44.1323 29.0709 44.9708 28.9667C45.8093 28.8625 46.6599 29.0092 47.415 29.3882C48.1701 29.7672 48.7958 30.3615 49.213 31.096C49.6302 31.8305 49.8202 32.6722 49.7589 33.5146C49.6977 34.357 49.3879 35.1624 48.8688 35.8288C48.3497 36.4952 47.6446 36.9928 46.8426 37.2587C45.7669 37.6137 44.5942 37.5277 43.5819 37.0195C42.5695 36.5114 41.8001 35.6226 41.4424 34.5481"
                    fill="#B85A1B"
                  />
                  <path
                    d="M41.2603 32.521C41.3864 31.7423 41.7225 31.0128 42.2323 30.4108C42.7422 29.8088 43.4066 29.3571 44.154 29.1043C44.6885 28.9282 45.2524 28.8592 45.8136 28.9013C46.3748 28.9434 46.9221 29.0957 47.4243 29.3497C47.9265 29.6036 48.3736 29.954 48.7401 30.381C49.1065 30.8079 49.3851 31.3029 49.5599 31.8376C49.671 32.1794 49.7418 32.5329 49.7707 32.8911C48.4849 33.7051 46.9807 34.1059 45.4602 34.0397C43.9398 33.9736 42.4761 33.4436 41.266 32.521H41.2603Z"
                    fill="#E1762F"
                  />
                  <path
                    d="M21.5561 0C22.6234 0.032779 23.6345 0.485531 24.3695 1.25975C25.1045 2.03398 25.504 3.06703 25.4809 4.13417C25.4961 4.66357 25.4065 5.19075 25.2172 5.68542C25.0279 6.1801 24.7427 6.63251 24.378 7.01667C24.0133 7.40083 23.5763 7.70917 23.092 7.92397C22.6077 8.13877 22.0858 8.2558 21.5561 8.26833C20.4934 8.22969 19.4886 7.77419 18.7594 7.00043C18.0302 6.22668 17.6351 5.19695 17.6598 4.13417C17.6351 3.07138 18.0302 2.04165 18.7594 1.2679C19.4886 0.494148 20.4934 0.0386433 21.5561 0"
                    fill="#7F3E13"
                  />
                  <path
                    d="M21.5564 1.13879C20.7948 1.17429 20.0779 1.50882 19.5617 2.06968C19.0454 2.63054 18.7715 3.37241 18.7993 4.13407C18.773 4.89778 19.0489 5.64109 19.5671 6.20284C20.0853 6.7646 20.8041 7.09954 21.5677 7.13504C22.3319 7.10249 23.0519 6.76825 23.5699 6.20565C24.0878 5.64305 24.3614 4.89804 24.3305 4.13407C24.3523 3.37119 24.0739 2.63018 23.5553 2.07016C23.0366 1.51014 22.3189 1.17575 21.5564 1.13879"
                    fill="#B85A1B"
                  />
                  <path
                    d="M21.5562 2.27771C21.326 2.29499 21.1016 2.35749 20.8956 2.46163C20.6897 2.56577 20.5063 2.7095 20.3561 2.88459C20.2058 3.05968 20.0915 3.26269 20.0199 3.48199C19.9482 3.70128 19.9205 3.93256 19.9384 4.16257C19.9161 4.62489 20.074 5.07785 20.3789 5.42618C20.6838 5.77452 21.1119 5.99109 21.5732 6.03035C22.0344 5.99538 22.4629 5.77923 22.7651 5.42919C23.0672 5.07914 23.2183 4.62372 23.1853 4.16257C23.2138 3.69794 23.0592 3.24063 22.7548 2.88841C22.4504 2.53618 22.0202 2.317 21.5562 2.27771"
                    fill="#63300F"
                  />
                  <path
                    d="M38.2009 0C39.2731 0.0253908 40.2915 0.474909 41.0326 1.24994C41.7737 2.02498 42.1771 3.06223 42.1542 4.13417C42.1829 4.66719 42.1027 5.20047 41.9184 5.70148C41.7342 6.20248 41.4497 6.6607 41.0824 7.04817C40.7152 7.43565 40.2728 7.74424 39.7822 7.95514C39.2917 8.16603 38.7633 8.2748 38.2293 8.2748C37.6954 8.2748 37.167 8.16603 36.6765 7.95514C36.1859 7.74424 35.7435 7.43565 35.3762 7.04817C35.009 6.6607 34.7245 6.20248 34.5403 5.70148C34.356 5.20047 34.2758 4.66719 34.3045 4.13417C34.2813 3.0718 34.6769 2.0429 35.4058 1.26948C36.1348 0.496051 37.1386 0.0401 38.2009 0"
                    fill="#7F3E13"
                  />
                  <path
                    d="M38.201 1.13879C37.4368 1.17135 36.7168 1.50559 36.1989 2.06819C35.6809 2.63079 35.4074 3.3758 35.4382 4.13977C35.4082 4.5211 35.4575 4.90451 35.5828 5.2659C35.7082 5.6273 35.907 5.95886 36.1667 6.23974C36.4265 6.52062 36.7415 6.74475 37.0921 6.89803C37.4427 7.05132 37.8212 7.13045 38.2038 7.13045C38.5865 7.13045 38.965 7.05132 39.3155 6.89803C39.6661 6.74475 39.9812 6.52062 40.2409 6.23974C40.5007 5.95886 40.6995 5.6273 40.8248 5.2659C40.9502 4.90451 40.9994 4.5211 40.9694 4.13977C40.9913 3.37725 40.7139 2.63643 40.1966 2.07565C39.6793 1.51487 38.963 1.17863 38.201 1.13879"
                    fill="#B85A1B"
                  />
                  <path
                    d="M38.2012 2.27771C37.9706 2.29289 37.7453 2.35346 37.5382 2.45593C37.3311 2.55841 37.1463 2.70078 36.9944 2.87486C36.8425 3.04894 36.7264 3.25131 36.653 3.47034C36.5795 3.68937 36.5501 3.92074 36.5664 4.15118C36.544 4.6135 36.7019 5.06646 37.0068 5.41479C37.3117 5.76313 37.7399 5.9797 38.2012 6.01896C38.4316 6.0045 38.6568 5.94461 38.8638 5.84274C39.0709 5.74086 39.2558 5.59902 39.4077 5.4254C39.5597 5.25177 39.6758 5.04978 39.7493 4.83109C39.8228 4.6124 39.8523 4.38132 39.8361 4.15118C39.8599 3.68791 39.7027 3.2335 39.3977 2.88393C39.0926 2.53436 38.6636 2.31699 38.2012 2.27771"
                    fill="#4F2800"
                  />
                  <path
                    d="M44.4329 14.851H44.3588V7.11226C44.3588 4.78323 38.8903 3.47351 32.7097 3.16601C32.7142 3.00448 32.6868 2.84365 32.6291 2.69271C32.5714 2.54176 32.4845 2.40365 32.3735 2.28626C32.2624 2.16887 32.1292 2.0745 31.9817 2.00854C31.8341 1.94259 31.675 1.90633 31.5134 1.90184C31.3518 1.89735 31.191 1.92473 31.04 1.98239C30.889 2.04006 30.7508 2.1269 30.6334 2.23794C30.3962 2.4622 30.2579 2.77146 30.2488 3.09768H29.5254C29.4972 2.78447 29.3506 2.49378 29.1155 2.28484C28.8804 2.07591 28.5744 1.96444 28.26 1.97315C27.9455 1.98186 27.6462 2.1101 27.423 2.33173C27.1999 2.55336 27.0696 2.85172 27.0588 3.16601C20.8839 3.46212 15.4154 4.77184 15.4154 7.08379V14.8055H15.3357C14.7908 15.0116 14.3223 15.3798 13.9933 15.8604C13.6643 16.341 13.4906 16.9109 13.4957 17.4932V23.1535L29.8786 28.4721L46.2614 23.0738V17.5388C46.2674 16.9578 46.0954 16.3889 45.7685 15.9084C45.4416 15.428 44.9756 15.059 44.4329 14.851V14.851Z"
                    fill="#7F3E13"
                  />
                  <path
                    d="M16.7313 7.40292V15.7225L16.0478 15.9845C15.6818 16.1178 15.3663 16.3614 15.1449 16.6817C14.9234 17.002 14.807 17.3832 14.8117 17.7725V23.6207L29.9014 28.4724L45.0026 23.5125V17.7725C45.0065 17.3864 44.8924 17.0084 44.6756 16.6888C44.4587 16.3693 44.1495 16.1236 43.7892 15.9845L43.1057 15.7225V7.40292C43.0772 3.45667 16.7883 3.45667 16.7598 7.40292H16.7313Z"
                    fill="#B85A1B"
                  />
                  <path
                    d="M16.7313 7.40272V15.7223L16.0478 15.9843C15.6818 16.1176 15.3663 16.3612 15.1449 16.6815C14.9234 17.0018 14.807 17.383 14.8117 17.7723V23.6205L29.9014 28.4722V25.3061C24.6892 25.3345 19.4713 21.8894 20.8441 15.1415H29.9014V4.453C23.3164 4.453 16.7313 5.44953 16.7313 7.40272Z"
                    fill="#E1762F"
                  />
                  <path
                    d="M20.1319 13.3762H39.7047C42.4675 28.0679 17.1299 27.9654 20.1319 13.3762Z"
                    fill="#E5895B"
                  />
                  <path
                    d="M21.0778 14.5378C20.8158 16.8156 21.2886 18.9852 22.9747 20.6992C24.7691 22.4872 27.4521 23.2503 29.93 23.2275C32.408 23.2047 34.8574 22.4645 36.6233 20.7789C37.4387 19.9722 38.0521 18.9844 38.4134 17.8959C38.7747 16.8074 38.8737 15.649 38.7025 14.515L21.0778 14.5378Z"
                    fill="#E5895B"
                  />
                  <path
                    d="M33.1709 21.3827C34.4868 23.091 38.3318 22.3849 37.1584 18.7747L33.1709 21.3827V21.3827Z"
                    fill="#FAD395"
                  />
                  <path
                    d="M31.958 21.0126C33.4334 22.9088 38.224 21.7756 37.1246 17.6699L31.958 21.0126Z"
                    fill="#4F2800"
                  />
                  <path
                    d="M36.3841 17.8521C36.5306 17.8179 36.6847 17.843 36.8128 17.9219C36.9408 18.0009 37.0325 18.1273 37.0676 18.2735C37.1142 18.3487 37.1427 18.4338 37.1507 18.5219C37.1587 18.61 37.146 18.6988 37.1137 18.7812C37.0814 18.8636 37.0303 18.9373 36.9645 18.9965C36.8988 19.0557 36.8201 19.0988 36.7348 19.1223C36.6494 19.1458 36.5598 19.1492 36.4729 19.132C36.3861 19.1148 36.3044 19.0777 36.2345 19.0235C36.1645 18.9693 36.1081 18.8995 36.0698 18.8198C36.0315 18.74 36.0123 18.6524 36.0138 18.5639C35.9691 18.4204 35.9829 18.2651 36.0522 18.1318C36.1216 17.9984 36.2409 17.8979 36.3841 17.8521V17.8521ZM33.251 20.0388C33.3976 20.0045 33.5517 20.0296 33.6798 20.1086C33.8078 20.1875 33.8994 20.3139 33.9346 20.4602C33.9793 20.6037 33.9655 20.759 33.8962 20.8923C33.8268 21.0257 33.7075 21.1262 33.5643 21.172C33.4178 21.2062 33.2637 21.1811 33.1356 21.1022C33.0076 21.0232 32.916 20.8968 32.8808 20.7506C32.836 20.6071 32.8499 20.4518 32.9192 20.3184C32.9886 20.1851 33.1079 20.0846 33.251 20.0388"
                    fill="white"
                  />
                  <path
                    d="M35.1708 21.7527C35.7332 21.5877 36.2339 21.2592 36.6092 20.809C36.9845 20.3588 37.2174 19.8072 37.2784 19.2244C36.4354 19.0934 34.96 20.3633 35.1708 21.7527"
                    fill="#AD6151"
                  />
                  <path
                    d="M22.1316 13.3762H37.6486C39.8588 23.8825 19.7619 23.78 22.1316 13.3762"
                    fill="#FAD395"
                  />
                  <path
                    d="M29.8787 19.3613C29.9772 18.7308 30.2296 18.1342 30.6136 17.6244C32.4877 17.334 34.094 15.9161 33.5928 13.3821C32.4066 12.9658 31.1587 12.7521 29.9015 12.75L29.1382 15.1474L29.8787 19.3613V19.3613Z"
                    fill="#4F2800"
                  />
                  <path
                    d="M29.8786 19.3613C29.778 18.7304 29.5237 18.1339 29.1381 17.6244C27.2697 17.334 25.6633 15.9161 26.1646 13.3821C27.3489 12.9663 28.5949 12.7526 29.8501 12.75L29.8786 19.3613Z"
                    fill="#63300F"
                  />
                  <path
                    d="M27.1389 13.7464C28.9425 13.2725 30.838 13.2725 32.6416 13.7464C33.4106 15.6654 26.2958 15.6427 27.1389 13.7464Z"
                    fill="#AD6151"
                  />
                  <path
                    d="M26.9278 8.3993C27.3407 8.39817 27.7445 8.51959 28.0883 8.74817C28.432 8.97675 28.7001 9.30219 28.8586 9.68326C29.0171 10.0643 29.0589 10.4838 28.9786 10.8887C28.8984 11.2935 28.6997 11.6654 28.4077 11.9572C28.1158 12.249 27.7438 12.4476 27.3389 12.5279C26.9339 12.6081 26.5142 12.5663 26.133 12.4079C25.7518 12.2494 25.4263 11.9814 25.1976 11.6378C24.969 11.2942 24.8475 10.8905 24.8486 10.4778C24.8501 9.92699 25.0697 9.39919 25.4593 9.00973C25.8489 8.62027 26.3769 8.4008 26.9278 8.3993"
                    fill="white"
                  />
                  <path
                    d="M27.0874 9.13974C27.3952 9.13975 27.6961 9.23107 27.952 9.40214C28.2078 9.57321 28.4071 9.81634 28.5247 10.1007C28.6422 10.3851 28.6727 10.698 28.6122 10.9997C28.5517 11.3014 28.403 11.5784 28.185 11.7956C27.9669 12.0128 27.6893 12.1604 27.3873 12.2197C27.0852 12.2791 26.7724 12.2475 26.4883 12.1289C26.2043 12.0104 25.9618 11.8103 25.7916 11.5539C25.6214 11.2975 25.5311 10.9963 25.5323 10.6886C25.5307 10.4843 25.57 10.2818 25.6476 10.0928C25.7253 9.9038 25.8399 9.73219 25.9847 9.58798C26.1295 9.44377 26.3016 9.32986 26.4909 9.2529C26.6802 9.17593 26.883 9.13747 27.0874 9.13974"
                    fill="#1B1D23"
                  />
                  <path
                    d="M32.8806 8.3993C32.4678 8.39817 32.0639 8.51959 31.7202 8.74817C31.3764 8.97675 31.1083 9.30219 30.9498 9.68326C30.7913 10.0643 30.7496 10.4838 30.8298 10.8887C30.9101 11.2935 31.1088 11.6654 31.4007 11.9572C31.6926 12.249 32.0646 12.4476 32.4696 12.5279C32.8745 12.6081 33.2942 12.5663 33.6754 12.4079C34.0566 12.2494 34.3821 11.9814 34.6108 11.6378C34.8395 11.2942 34.9609 10.8905 34.9598 10.4778C34.9583 9.92699 34.7388 9.39919 34.3492 9.00973C33.9596 8.62027 33.4316 8.4008 32.8806 8.3993"
                    fill="white"
                  />
                  <path
                    d="M32.7494 9.13965C32.4416 9.13965 32.1407 9.23097 31.8849 9.40204C31.629 9.57312 31.4297 9.81625 31.3122 10.1006C31.1946 10.385 31.1642 10.6979 31.2246 10.9996C31.2851 11.3013 31.4338 11.5783 31.6518 11.7955C31.8699 12.0127 32.1475 12.1603 32.4496 12.2196C32.7516 12.279 33.0645 12.2474 33.3485 12.1288C33.6326 12.0103 33.8751 11.8102 34.0452 11.5538C34.2154 11.2974 34.3057 10.9962 34.3046 10.6885C34.2986 10.2786 34.1327 9.88721 33.8422 9.59785C33.5517 9.30849 33.1595 9.14406 32.7494 9.13965Z"
                    fill="#1B1D23"
                  />
                  <path
                    d="M28.4316 7.74442C27.7009 7.58011 26.9447 7.56296 26.2073 7.69397C25.4698 7.82499 24.7659 8.10154 24.1365 8.50748C23.6865 5.95067 27.9816 5.18762 28.4316 7.74442Z"
                    fill="#63300F"
                  />
                  <path
                    d="M30.9551 6.45181C31.6858 6.2875 32.442 6.27035 33.1794 6.40137C33.9169 6.53238 34.6208 6.80893 35.2502 7.21487C35.7002 4.65806 31.4336 3.92348 30.9551 6.45181Z"
                    fill="#4F2800"
                  />
                  <path
                    d="M46.2387 23.0909V39.0354C46.2387 39.5545 46.0332 40.0524 45.6671 40.4205C45.301 40.7887 44.804 40.997 44.2848 41H15.4155C14.8997 40.997 14.4059 40.7908 14.0412 40.4263C13.6765 40.0617 13.4703 39.5681 13.4673 39.0525V23.2504L29.8501 27.1739L46.2387 23.0909Z"
                    fill="#7EAABA"
                  />
                  <path
                    d="M44.9456 24.7537L29.8787 28.4721L14.8117 24.8846V38.7221C14.8094 38.8506 14.833 38.9783 14.8811 39.0975C14.9292 39.2167 15.0009 39.325 15.0918 39.4158C15.1827 39.5067 15.291 39.5784 15.4103 39.6265C15.5295 39.6746 15.6572 39.6982 15.7858 39.6959H43.9943C44.1257 39.703 44.2571 39.6829 44.3804 39.6369C44.5036 39.5909 44.6161 39.52 44.7107 39.4286C44.8053 39.3372 44.8801 39.2273 44.9303 39.1058C44.9805 38.9842 45.0051 38.8536 45.0026 38.7221V24.7537H44.9456Z"
                    fill="#D3E8EF"
                  />
                  <path
                    d="M29.8787 28.4723L14.8117 24.8848V38.7223C14.8093 38.8513 14.8331 38.9794 14.8816 39.099C14.9301 39.2186 15.0023 39.3271 15.0938 39.4181C15.1853 39.509 15.2943 39.5805 15.4142 39.6283C15.5341 39.6761 15.6624 39.6991 15.7915 39.696H29.9014V28.4723H29.8787Z"
                    fill="white"
                  />
                  <path
                    d="M29.9014 35.875H42.5246V37.5833H29.9071V35.875H29.9014ZM29.9014 32.3729H42.5246V34.0813H29.9071V32.3729H29.9014Z"
                    fill="#036AAB"
                  />
                  <path
                    d="M29.9012 37.5833V35.875H17.2324V37.5833H29.9012ZM17.2324 32.39H29.9012V34.0983H17.2324V32.39Z"
                    fill="#0399ED"
                  />
                  <path
                    d="M22.3706 31.2853H23.9485V39.024H22.3706V31.2853Z"
                    fill="white"
                  />
                  <path
                    d="M17.916 24.2812C21.9035 25.2265 25.891 26.2003 29.8785 27.1285L25.6346 31.1772C22.8662 29.5429 20.1262 27.7606 17.9388 24.2812"
                    fill="#7EAABA"
                  />
                  <path
                    d="M21.2886 26.4392C22.5387 27.6345 23.927 28.6763 25.4242 29.5427L27.2185 27.8343C25.2704 27.3845 23.2652 26.8834 21.2886 26.4392"
                    fill="white"
                  />
                  <path
                    d="M41.835 24.2812C37.8475 25.2265 33.86 26.2002 29.8726 27.1284L34.1392 31.1544C36.9076 29.5201 39.6419 27.7377 41.8293 24.2584"
                    fill="#7EAABA"
                  />
                  <path
                    d="M38.4628 26.4392C37.2149 27.6371 35.8262 28.6792 34.3272 29.5427L32.5386 27.8343C34.5152 27.3845 36.4919 26.8834 38.4628 26.4392Z"
                    fill="white"
                  />
                </svg>
              </span>
              <span class="plugin-item-text"> WPForms </span>
            </span>
            <div class="plugin-item"  id="cntactForm7"  onmouseover="this.style['background-color'] = '#EFF2F7'" onmouseout="this.style['background-color'] = '#f9fafc'">
              <span class="plugin-item-img">
                <svg
                  width="44"
                  height="44"
                  viewBox="0 0 44 44"
                  fill="none"
                  xmlns="http://www.w3.org/2000/svg"
                >
                  <path
                    d="M22 43C33.598 43 43 33.598 43 22C43 10.402 33.598 1 22 1C10.402 1 1 10.402 1 22C1 33.598 10.402 43 22 43Z"
                    fill="#33C6F4"
                  />
                  <path
                    d="M42.0336 28.8034C32.377 24.4642 24.0288 14.1533 23.1917 13.5946C22.3546 13.036 21.2319 13.7339 21.2319 13.7339C20.5322 12.6147 19.413 13.8731 19.413 13.8731C14.2331 22.6907 1.89453 28.8034 1.89453 28.8034C1.89453 28.8034 6.8939 42.4104 22.2728 42.4104C37.9084 42.4104 42.0336 28.8034 42.0336 28.8034Z"
                    fill="#1B447E"
                  />
                  <path
                    d="M12.5474 21.6944C12.5474 21.6944 15.3462 20.4288 14.7857 22.5351C14.2252 24.6415 11.9869 27.7133 12.6866 27.8525C13.3863 27.9917 15.3534 26.0336 15.9066 26.3121C16.4599 26.5905 16.8866 26.4531 16.6063 27.433C16.3261 28.413 16.0459 29.1127 16.6063 29.1127C17.1668 29.1127 17.5863 27.8471 18.286 26.5923C18.9857 25.3376 20.1048 25.8926 20.5243 26.3121C20.9437 26.7315 22.2039 29.1127 23.1839 28.8324C24.1638 28.5522 24.7225 27.292 24.303 24.4932C23.8836 21.6944 24.5833 20.8555 24.5833 20.8555C24.5833 20.8555 25.4222 20.436 26.9626 22.3941C28.503 24.3522 31.3018 27.292 31.7195 27.1528C32.1371 27.0136 29.7614 23.9345 30.3201 23.6543C30.8787 23.3741 31.8283 23.6909 32.9493 24.6709C32.9493 24.6709 34.7 27.0862 34.7 25.5476L23.1839 13.8585L21.224 13.9959L19.4051 14.1351C19.4051 14.1351 14.9267 20.8555 12.5474 21.6944Z"
                    fill="white"
                  />
                  <path
                    d="M42.6511 28.8033C33.55 25.0421 24.1847 14.1065 23.3957 13.6222C22.6068 13.138 21.5486 13.7429 21.5486 13.7429C20.8891 12.7728 19.8344 13.8636 19.8344 13.8636C14.9524 21.5066 1.89453 28.8033 1.89453 28.8033"
                    stroke="#221E1F"
                    stroke-width="2"
                    stroke-miterlimit="10"
                  />
                  <path
                    d="M22 43C33.598 43 43 33.598 43 22C43 10.402 33.598 1 22 1C10.402 1 1 10.402 1 22C1 33.598 10.402 43 22 43Z"
                    stroke="#221E1F"
                    stroke-width="2"
                    stroke-miterlimit="10"
                  />
                </svg>
              </span>
              <span class="plugin-item-text" style="bottom: 17px;"> Contact Form 7 </span>
            </div>
            <div class="plugin-item" id="optinMonster"  onmouseover="this.style['background-color'] = '#EFF2F7'" onmouseout="this.style['background-color'] = '#f9fafc'">
              <span class="plugin-item-img" style="top: 2px; position: relative;">
			  <svg width="43" height="37" viewBox="0 0 43 37" fill="none" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
					<rect width="43" height="37" fill="url(#pattern3)"/>
					<defs>
					<pattern id="pattern3" patternContentUnits="objectBoundingBox" width="1" height="1">
					<use xlink:href="#image0_40_381" transform="translate(-0.0587369) scale(0.00102803 0.00119474)"/>
					</pattern>
					<image id="image0_40_381" width="1087" height="837" xlink:href="data:image/png;base64,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"/>
					</defs>
			  </svg>

              </span>
              <span class="plugin-item-text" style="bottom: 11px;">
                OptinMonster
              </span>
            </div>
          </div>
        </div>
		<div class="copyright">© 2023 Brevo. All rights reserved</div>
      </div>
    </div>
    <div id="myModal" class="modal">
      <!-- Modal content -->
      <div class="modal-content">
		<div class="modal-heading"><div id="pluginName"></div><span class="close">&times;</span></div>
      	<div class="modalContent">
		  <embed src="" style="border:none;" title="Embed" id="embed_plugin"></embed>
		</div>
      </div>
    </div>
    <script>
      var modal = document.getElementById("myModal");
      var wp_btn = document.getElementById("wp_forms");
	  var cf7_btn = document.getElementById("cntactForm7");
	  var om_btn = document.getElementById("optinMonster");
	  var embedTag = document.getElementById('embed_plugin');

      var span = document.getElementsByClassName("close")[0];

	  var pluginName='';
      wp_btn.onclick = function () {
        modal.style.display = "block";
		document.getElementById('pluginName').innerHTML='WPForms';
		embedTag.setAttribute('src',window.location.protocol + "//" + window.location.hostname + '/wp-admin/plugin-install.php?tab=plugin-information&plugin=wpforms-lite&&width=772&height=661');
      };
	  cf7_btn.onclick = function () {
        modal.style.display = "block";
		document.getElementById('pluginName').innerHTML='Contact Form 7';
		embedTag.setAttribute('src',window.location.protocol + "//" + window.location.hostname + '/wp-admin/plugin-install.php?tab=plugin-information&plugin=contact-form-7&TB_iframe=true&width=772&height=661')
      };
	  om_btn.onclick = function () {
        modal.style.display = "block";
		document.getElementById('pluginName').innerHTML='OptinMonster';
		embedTag.setAttribute('src',window.location.protocol + "//" + window.location.hostname + '/wp-admin/plugin-install.php?tab=plugin-information&plugin=optinmonster&TB_iframe=true&width=772&height=661')
      };

      span.onclick = function () {
        modal.style.display = "none";
      };

      window.onclick = function (event) {
        if (event.target == modal) {
          modal.style.display = "none";
        }
      };
	  // If content grows in length, reduce the font-size
	  const goToDash = document.getElementById('goToDashButton');
	  if(goToDash.textContent.length > 50){
			goToDash.style.fontSize='16px';
	  }
    </script>
  </body>
</html>
