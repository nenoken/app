// Generated by CoffeeScript 1.6.1
(function() {
	var SignatureView,
		__hasProp = {}.hasOwnProperty,
		__extends = function(child, parent) { for (var key in parent) { if (__hasProp.call(parent, key)) child[key] = parent[key]; } function ctor() { this.constructor = child; } ctor.prototype = parent.prototype; child.prototype = new ctor(); child.__super__ = parent.prototype; return child; };

	SignatureView = (function(_super) {

		__extends(SignatureView, _super);

		function SignatureView() {
			return SignatureView.__super__.constructor.apply(this, arguments);
		}

		SignatureView.prototype.events = {
			'click a.description-link': 'switchToDescription',
			'click a.snippet-link': 'switchToSnippet',
			'mousedown .snippet': 'snippetToTextArea'
		};

		SignatureView.prototype.initialize = function() {};

		SignatureView.prototype.render = function() {
			var template;
			template = this.template();
			$(this.el).html(template(this.model));
			this.switchToDescription();
			this.isParam = this.model.isParam;
			if (this.isParam) {
				$('.notice', $(this.el)).text('Click to set as parameter value');
			}
			return this;
		};

		SignatureView.prototype.template = function() {
			return Handlebars.templates.signature;
		};

		SignatureView.prototype.switchToDescription = function(e) {
			if (e != null) {
				e.preventDefault();
			}
			$(".snippet", $(this.el)).hide();
			$(".description", $(this.el)).show();
			$('.description-link', $(this.el)).addClass('selected');
			return $('.snippet-link', $(this.el)).removeClass('selected');
		};

		SignatureView.prototype.switchToSnippet = function(e) {
			if (e != null) {
				e.preventDefault();
			}
			$(".description", $(this.el)).hide();
			$(".snippet", $(this.el)).show();
			$('.snippet-link', $(this.el)).addClass('selected');
			return $('.description-link', $(this.el)).removeClass('selected');
		};

		SignatureView.prototype.snippetToTextArea = function(e) {
			var textArea;
			if (this.isParam) {
				if (e != null) {
					e.preventDefault();
				}
				textArea = $('textarea', $(this.el.parentNode.parentNode.parentNode));
				if ($.trim(textArea.val()) === '') {
					return textArea.val(this.model.sampleJSON);
				}
			}
		};

		return SignatureView;

	})(Backbone.View);

	window.SignatureView = SignatureView; // make it public
}).call(this);
