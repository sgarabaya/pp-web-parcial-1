const [isFunction, isObservable, computation] = [
  (o) => typeof o == "function",
  (o) => isFunction(o) && "watch" in o,
  [],
];

const createObservable = (v) => {
  let value;
  const subscribers = new Set();
  const watch = (fn, immediate = false) => {
    subscribers.add(fn);
    if (immediate) fn(value);
  };

  const _func = (...args) => {
    if (args.length > 0) {
      value = args[0];
      subscribers.forEach((f) => f(value));
    } else if (computation.length > 0)
      watch(computation[computation.length - 1]);
    return value;
  };

  _func.watch = watch;
  if (isFunction(v)) {
    computation.push(() => _func(v()));
    try {
      _func(v());
    } finally {
      computation.pop();
    }
  } else value = v;
  return _func;
};

const bindInputs = (model) => {
  document.querySelectorAll("[x-bind]").forEach((el) => {
    const key = el.getAttribute("x-bind");
    const observable = model[key];

    if (observable && isObservable(observable)) {
      observable.watch((val) => (el.value = val), true);
      el.addEventListener("input", (e) => observable(e.target.value));
    }
  });
};

const $ = (id) => document.getElementById(id);
document.addEventListener("DOMContentLoaded", () => {
  const dialog = $("create-dialog");

  const data = {
    name: createObservable(""),
    lastName: createObservable(""),
    prepEmail: createObservable("@ruta9.ar"),
    email: createObservable(""),
    role: createObservable("SALES"),
    password: createObservable(""),
  };

  function reset() {
    data.name("");
    data.lastName("");
    data.email("");
    data.role("SALES");
    data.password("");
  }

  $("user_email").onfocus = (e) => {
    if (e.target.value.length > 0) return;
    data.email(
      `${data.name().toLowerCase()}.${data.lastName().toLowerCase()}@ruta9.ar`,
    );
  };

  $("button-create").onclick = (ev) => {
    dialog.open = true;

    bindInputs(data);

    $("button-cancel").onclick = (ev) => {
      ev.preventDefault();
      reset();
      dialog.open = false;
    };
  };
});

function edit(userId) {
  const dialog = $("edit-dialog");
  dialog.open = true;

  const elements = [...document.querySelectorAll("#edit-dialog [name]")];

  console.log(elements);
}
