* {
  background: inherit;
  color: inherit;
  font: inherit;
  box-sizing: border-box;
  margin: 0;
}

body {
  margin: 0px;
  background: #ffffff;

  font-family: "Source Sans Pro", sans-serif;
  font-size: 17px;
  font-weight: 400;
}

label {
  display: block;
  margin-bottom: 24px;
}

/* fix rendering issues on iPhone */
input {
  -webkit-appearance: none;
  -moz-appearance: none;
  appearance: none;
}

input,
input ~ i {
  border: none;
  border-radius: 0;
  transition: all 200ms ease-in-out;
  outline: none !important;
}

[type=text],
[type=email] {
  display: block;
  width: 100%;
  background: #f7f7f7 !important;
  padding: 10px;
  text-indent: 1ex;
}

[type=text]:invalid:focus,
[type=email]:invalid:focus {
  box-shadow: 0 1px 0 #e50054;
}
[type=text]:valid:focus,
[type=email]:valid:focus {
  box-shadow: 0 1px 0 #c9d25b;
}

::placeholder {
  opacity: 0.25;
}

[type=checkbox] {
  opacity: 0;
  width: 0px;
  margin: 0px;
  position: absolute;
}

[type=checkbox] ~ span:before {
  display: inline-block;
  content: '';
  width: 22px;
  height: 22px;
  border-radius: 11px;
  background: #fff;
  border: 1px solid #e7e7e7;
  vertical-align: -5px;
  margin-right: 6px;
  cursor: pointer;
}

[type=checkbox]:checked ~ span:before {
  background: #c9d25b;
  border-color: #c9d25b;
}

[type=checkbox]:invalid:focus ~ span:before {
  border-color: #e50054;
}

[type=checkbox]:valid:focus ~ span:before {
  border-color: #c9d25b;
}

a {
  color: #e50054;
}

a:hover {
  color: #8a0435;
}

[type=submit] {
  border: none;
  display: block;
  color: #fff;
  background: #e50054;
  line-height: 48px;
  max-width: 300px;
  margin: 0 auto;
}

[type=submit] ~ sub {
  display: none;
  width: 300px;
  margin: 0 auto;
}

:valid [type=submit] {
  background: #c9d25b;
}

:valid [type=submit]:focus,
:valid [type=submit]:hover {
  cursor: pointer;
  background: #b9c24b;
  text-decoration: underline;
}

:invalid [type=submit] {
  cursor: not-allowed;
}

sub {
  color: #e50054;
  font-size: 13px;
  display: block;
}

.success {
  border: 4px solid #c9d25b;
}

.failure {
  border: 4px solid #e50054;
}

p {
  margin: 14px 21px;
}

.google,
.office365 {
  cursor: not-allowed;
  display: block;
  max-width: 300px;
  height: 64px;
  margin-bottom: 14px;
  background-color: #f7f7f7;
  background-size: 48px 48px;
  background-repeat: no-repeat;
  background-position: 16px 8px;
  border: none;
  line-height: 64px;
  text-align: left;
  padding-left: 80px;
  opacity: 0.1; /* fixme */
}
.google {
  background-image: url("/sso/google.png");
}
.office365 {
  background-image: url("/sso/office365.png");
}
p.separator {
  text-align: center;
  margin: 24px;
  opacity: 0.1; /* fixme */
}
