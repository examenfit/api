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

  font-family: sans-serif;
  font-family: Source Sans Pro;
  font-size: 17px;
  font-weight: 400;
}

label {
  display: block;
  margin-bottom: 24px;
}

input,
input ~ i {
  border: none;
  transition: all 200ms ease-in-out;
  outline: none !important;
}

[type=text],
[type=email] {
  display: block;
  width: 100%;
  background: #f7f7f7;
  padding: 10px;
  text-indent: 1ex;
}

[type=text]:focus,
[type=email]:focus {
  box-shadow: 0 1px 0 #e50054;
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

[type=checkbox] ~ i {
  display: inline-block;
  width: 22px;
  height: 22px;
  background: #f7f7f7;
  background: #ffffff;
  vertical-align: -6px;
  margin-right: 6px;
  cursor: pointer;
  background: #f7f7f7;
}

[type=checkbox]:checked ~ i {
  background: #c9d25b;
}

[type=checkbox]:focus ~ i {
  box-shadow: 0 1px 0 #e50054;
}

a {
  color: #e50054;
}
a:hover {
  color: #8a0435;
}

[type=submit] {
  display: block;
  color: #fff;
  background: #e50054;
  line-height: 48px;
  width: 300px;
  margin: 0 auto;
}

[type=submit] ~ sub {
  display: none;
  width: 300px;
  margin: 0 auto;
}
:invalid [type=submit]:hover ~ sub {
  display: block;
}

:valid [type=submit]:focus,
:valid [type=submit]:hover {
  background: #8a0435;
}

:invalid [type=submit] {
  cursor: not-allowed;
}

sub {
  color: #8a0435;
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
