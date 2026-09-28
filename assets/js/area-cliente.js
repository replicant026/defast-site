var supabaseClient;

function obterConfigCliente() {
  if (window.__CONFIG__ && window.__CONFIG__.SUPABASE_URL && window.__CONFIG__.SUPABASE_KEY) {
    return window.__CONFIG__;
  }

  var configNode = document.getElementById("defast-client-config");
  if (!configNode) return null;

  try {
    return JSON.parse(configNode.textContent || "{}");
  } catch (err) {
    console.error("Falha ao carregar configuracao do cliente:", err);
    return null;
  }
}

(function () {
  var cfg = obterConfigCliente();
  if (!cfg || !cfg.SUPABASE_URL || !cfg.SUPABASE_KEY) return;
  if (!window.supabase) return;

  var createClient = window.supabase.createClient;
  supabaseClient = createClient(cfg.SUPABASE_URL, cfg.SUPABASE_KEY);

  iniciarLogica();
})();

function iniciarLogica() {
  var loginScreen = document.getElementById("tela-login");
  var dashScreen = document.getElementById("tela-dashboard");

  supabaseClient.auth.onAuthStateChange(function (event, session) {
    if (session) {
      if (loginScreen) loginScreen.classList.add("hidden");
      if (dashScreen) dashScreen.classList.remove("hidden");
      carregarDadosUsuario(session);
    } else {
      if (dashScreen) dashScreen.classList.add("hidden");
      if (loginScreen) loginScreen.classList.remove("hidden");
    }
  });

  var formLogin = document.getElementById("form-login");
  if (formLogin) formLogin.addEventListener("submit", handleLogin);

  var btnReset = document.getElementById("btn-reset-password");
  if (btnReset) btnReset.addEventListener("click", handleResetPassword);

  var btnPortal = document.getElementById("btn-portal");
  if (btnPortal) btnPortal.addEventListener("click", abrirPortal);

  var btnLogout = document.getElementById("btn-logout");
  if (btnLogout) btnLogout.addEventListener("click", handleLogout);

  var formSenha = document.getElementById("form-senha");
  if (formSenha) formSenha.addEventListener("submit", atualizarSenha);

  verificarAuth();
}

async function verificarAuth() {
  var result = await supabaseClient.auth.getSession();
  var session = result.data.session;
  if (!session) {
    var dashScreen = document.getElementById("tela-dashboard");
    var loginScreen = document.getElementById("tela-login");
    if (dashScreen) dashScreen.classList.add("hidden");
    if (loginScreen) loginScreen.classList.remove("hidden");
  }
}

async function handleLogin(e) {
  e.preventDefault();
  if (!supabaseClient) return;

  var email = document.getElementById("email").value;
  var password = document.getElementById("password").value;
  var btn = document.getElementById("btn-entrar");

  try {
    btn.innerText = "Verificando...";
    btn.disabled = true;
    mostrarMsg("login", "", "");

    var result = await supabaseClient.auth.signInWithPassword({
      email: email,
      password: password,
    });
    if (result.error) throw result.error;
  } catch (err) {
    mostrarMsg("login", "erro", "Credenciais inválidas.");
    btn.innerText = "Entrar";
    btn.disabled = false;
  }
}

async function handleResetPassword() {
  if (!supabaseClient) return;
  var email = document.getElementById("email").value;
  if (!email) return mostrarMsg("login", "erro", "Preencha o e-mail primeiro.");

  try {
    mostrarMsg("login", "sucesso", "Enviando e-mail...");
    var result = await supabaseClient.auth.resetPasswordForEmail(email, {
      redirectTo: "https://defast.com.br/reset-senha",
    });
    if (result.error) throw result.error;
    mostrarMsg("login", "sucesso", "Link enviado! Verifique seu e-mail.");
  } catch (err) {
    mostrarMsg("login", "erro", err.message);
  }
}

async function carregarDadosUsuario(session) {
  var emailEl = document.getElementById("user-email");
  if (emailEl) emailEl.innerText = session.user.email;

  try {
    var result = await supabaseClient
      .from("user_licenses")
      .select("is_active, subscription_end, plan_id, plan_interval")
      .eq("user_id", session.user.id)
      .single();

    if (result.error && result.error.code !== "PGRST116") throw result.error;
    atualizarInterface(result.data);
  } catch (err) {
    console.error(err);
  }
}

function atualizarInterface(licenca) {
  var statusBadge = document.getElementById("status-badge");
  var planText = document.getElementById("plan-interval");
  var dateText = document.getElementById("data-validade");

  if (!licenca) {
    statusBadge.innerText = "Não iniciada";
    statusBadge.className =
      "px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800";
    planText.innerText = "-";
    dateText.innerText = "-";
    return;
  }

  var dataFim = new Date(licenca.subscription_end).toLocaleDateString("pt-BR");
  var nomePlano = licenca.plan_id || "Padrão";

  planText.innerText = nomePlano;
  dateText.innerText = dataFim;

  if (licenca.is_active) {
    statusBadge.innerText = "ATIVA";
    statusBadge.className =
      "px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800";
  } else {
    statusBadge.innerText = "INATIVA / CANCELADA";
    statusBadge.className =
      "px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800";
  }
}

async function abrirPortal() {
  if (!supabaseClient) return;
  var btn = document.getElementById("btn-portal");
  var txtOriginal = btn.innerText;
  try {
    btn.innerText = "Carregando...";
    btn.disabled = true;
    var result = await supabaseClient.functions.invoke("create-portal-session");
    if (result.error) throw result.error;
    window.location.href = result.data.url;
  } catch (err) {
    mostrarMsg("dash", "erro", "Erro ao abrir portal: " + err.message);
    btn.innerText = txtOriginal;
    btn.disabled = false;
  }
}

async function atualizarSenha(e) {
  e.preventDefault();
  if (!supabaseClient) return;

  var senhaAntiga = document.getElementById("senha-antiga").value;
  var senhaNova = document.getElementById("nova-senha").value;
  var senhaConfirm = document.getElementById("confirmar-senha").value;
  var btn = document.getElementById("btn-senha");

  if (!senhaAntiga) {
    mostrarMsg("dash", "erro", "Informe a senha atual.");
    return;
  }

  if (senhaNova !== senhaConfirm) {
    mostrarMsg("dash", "erro", "A nova senha e a confirmação não coincidem.");
    return;
  }

  try {
    btn.innerText = "Verificando...";
    btn.disabled = true;

    var sessionResult = await supabaseClient.auth.getSession();
    var session = sessionResult.data ? sessionResult.data.session : null;
    if (!session || !session.user || !session.user.email) {
      throw new Error("Sessao expirada. Faca login novamente.");
    }

    var email = session.user.email;

    var loginResult = await supabaseClient.auth.signInWithPassword({
      email: email,
      password: senhaAntiga,
    });
    if (loginResult.error) {
      mostrarMsg("dash", "erro", "Senha atual incorreta.");
      return;
    }

    btn.innerText = "Salvando...";
    var result = await supabaseClient.auth.updateUser({ password: senhaNova });
    if (result.error) throw result.error;
    mostrarMsg("dash", "sucesso", "Senha alterada com sucesso!");
    document.getElementById("senha-antiga").value = "";
    document.getElementById("nova-senha").value = "";
    document.getElementById("confirmar-senha").value = "";
  } catch (err) {
    mostrarMsg("dash", "erro", err.message);
  } finally {
    btn.innerText = "Salvar Nova Senha";
    btn.disabled = false;
  }
}

async function handleLogout() {
  if (supabaseClient) await supabaseClient.auth.signOut();
}

function mostrarMsg(area, tipo, texto) {
  var id = area === "login" ? "msg-login" : "msg-dash";
  var box = document.getElementById(id);

  if (!texto) {
    box.classList.add("hidden");
    return;
  }

  box.innerText = texto;
  box.className = "p-4 mb-4 rounded-md text-sm text-center";
  if (tipo === "erro") box.classList.add("bg-red-50", "text-red-700");
  else box.classList.add("bg-green-50", "text-green-700");

  box.classList.remove("hidden");
}
