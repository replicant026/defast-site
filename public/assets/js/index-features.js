(function () {
  "use strict";

  var featureData = {
    "filtrar-tags": {
      title: "Filtrar Tags",
      description:
        "Oculte ou isole tags específicas rapidamente na sua vista atual. Uma ferramenta essencial para manter seu projeto limpo e focado no que realmente importa durante a documentação.",
      video: "assets/videos/filtrar-tags.webm",
    },
    "colorir-tags": {
      title: "Colorir Tags",
      description:
        "Aplique cores automaticamente às tags de acordo com o sistema, circuito ou tipo de elemento. Facilita incrivelmente a conferência visual do seu projeto elétrico.",
      video: "assets/videos/colorir-tags.webm",
    },
    "campo-visao": {
      title: "Campo de Visão",
      description:
        "Ajuste rapidamente o recorte (Crop Region) e a profundidade da vista baseando-se nos elementos selecionados. Ideal para criar vistas de detalhe em segundos.",
      video: "assets/videos/camera-fov.webm",
    },
    "exportar-lote": {
      title: "Exportar em Lote",
      description:
        "Gere PDFs e DWGs de múltiplas pranchas e vistas simultaneamente, com nomenclatura padronizada e automática. Economiza horas de trabalho repetitivo na entrega de projetos.",
      video: "assets/videos/exportar.webm",
    },
    "tags-tomadas": {
      title: "Tag Tomadas",
      description:
        "Posicione automaticamente as tags em todas as tomadas do projeto, perfeitamente alinhadas, contendo as informações de potência, circuito e elevação, evitando sobreposições indesejadas.",
      video: "assets/videos/tag-tomadas.webm",
    },
    "tags-luminarias": {
      title: "Tag Luminárias",
      description:
        "Identifique rapidamente todas as luminárias do modelo com suas respectivas potências, circuitos e interruptores associados. Funciona perfeitamente inclusive em forros inclinados ou curvos.",
      video: "assets/videos/tag-luminarias.webm",
    },
    "tags-interruptores": {
      title: "Tag Interruptores",
      description:
        "A ferramenta Auto Tag Interruptores resolve um dos maiores desafios do detalhamento elétrico: posicionar de forma legível as letras de comando, especialmente em conjuntos de interruptores múltiplos.",
      video: "assets/videos/tag-interruptores.webm",
    },
    "numerar-interruptores": {
      title: "Numerar Interruptores",
      description:
        "Crie e associe sequências lógicas de letras de comando (a, b, c...) entre interruptores e luminárias de forma 100% automatizada e livre de erros de digitação.",
      video: "assets/videos/numerar-interruptores.webm",
    },
    "tags-fiacao": {
      title: "Tags Fiação",
      description:
        "Adicione a simbologia de fiação (fase, neutro, retorno, terra) e a bitola nos eletrodutos com apenas um clique, lendo os dados reais e instantâneos dos circuitos modelados no Revit.",
      video: "assets/videos/tags-fiacao.webm",
    },
    "criar-conduites": {
      title: "Criar Conduítes",
      description:
        "Automatize a modelagem de eletrodutos 3D. Selecione os elementos de destino e deixe o plugin traçar as rotas mais eficientes, desviando automaticamente de interferências arquitetônicas.",
      video: "assets/videos/criar-conduites.webm",
    },
    "dimensionar-circuitos": {
      title: "Dimensionar Circuitos",
      description:
        "Calcule e aplique automaticamente a bitola dos condutores e as correntes dos disjuntores baseando-se na carga instalada, distância e método de instalação, seguindo rigorosamente a NBR 5410.",
      video: "assets/videos/dimensionar-circuitos.webm",
    },
    "balancear-fases": {
      title: "Balancear Fases",
      description:
        "Distribua as cargas dos circuitos entre as fases (R, S, T) do quadro elétrico com um único clique para obter o menor desequilíbrio possível em todo o sistema.",
      video: "assets/videos/balancear-fases.webm",
    },
    "diagrama-unifilar": {
      title: "Diagrama Unifilar",
      description:
        "Gere instantaneamente o diagrama unifilar completo do seu quadro elétrico diretamente em uma Drafting View, com toda a simbologia e os dados extraídos em tempo real do modelo BIM.",
      video: "assets/videos/diagrama-unifilar.webm",
    },
    "diagrama-multifilar": {
      title: "Diagrama Multifilar",
      description:
        "Crie diagramas multifilares detalhados para quadros de comando e força, ilustrando claramente todas as conexões internas, contatores e relés modelados no seu projeto elétrico.",
      video: "assets/videos/diagrama-multifilar.webm",
    },
  };

  function clearChildren(node) {
    while (node.firstChild) {
      node.removeChild(node.firstChild);
    }
  }

  function renderVideo(container, videoPath) {
    var video = document.createElement("video");
    video.autoplay = true;
    video.loop = true;
    video.muted = true;
    video.playsInline = true;
    video.style.width = "100%";
    video.style.height = "100%";
    video.style.objectFit = "cover";
    video.style.borderRadius = "8px";

    var source = document.createElement("source");
    source.src = videoPath;
    source.type = "video/webm";

    video.appendChild(source);
    container.appendChild(video);
  }

  function renderFallback(container) {
    var svgNs = "http://www.w3.org/2000/svg";
    var svg = document.createElementNS(svgNs, "svg");
    svg.setAttribute("viewBox", "0 0 24 24");
    svg.setAttribute("fill", "none");
    svg.setAttribute("stroke", "#ccc");
    svg.setAttribute("stroke-width", "2");
    svg.setAttribute("stroke-linecap", "round");
    svg.setAttribute("stroke-linejoin", "round");

    var circle = document.createElementNS(svgNs, "circle");
    circle.setAttribute("cx", "12");
    circle.setAttribute("cy", "12");
    circle.setAttribute("r", "10");
    svg.appendChild(circle);

    var polygon = document.createElementNS(svgNs, "polygon");
    polygon.setAttribute("points", "10 8 16 12 10 16 10 8");
    polygon.setAttribute("fill", "#ccc");
    svg.appendChild(polygon);

    var text = document.createElement("span");
    text.textContent = "Preview da Ferramenta";

    container.appendChild(svg);
    container.appendChild(text);
  }

  function closeOpenDropdowns() {
    document.querySelectorAll(".dropdown.active").forEach(function (dropdown) {
      dropdown.classList.remove("active");
    });
  }

  function showFeature(id) {
    var data = featureData[id];
    if (!data) {
      return;
    }

    var title = document.getElementById("feature-title");
    var description = document.getElementById("feature-description");
    var media = document.getElementById("feature-media-placeholder");
    var details = document.getElementById("feature-details");
    if (!title || !description || !media || !details) {
      return;
    }

    title.textContent = data.title;
    description.textContent = data.description;

    clearChildren(media);
    if (data.video) {
      renderVideo(media, data.video);
    } else {
      renderFallback(media);
    }

    details.style.display = "flex";
    closeOpenDropdowns();
  }

  function closeDetails() {
    var details = document.getElementById("feature-details");
    if (details) {
      details.style.display = "none";
    }
  }

  function toggleDropdown(button) {
    var currentDropdown = button.parentElement;
    if (!currentDropdown) {
      return;
    }

    var isActive = currentDropdown.classList.contains("active");
    closeOpenDropdowns();

    if (!isActive) {
      currentDropdown.classList.add("active");
    }
  }

  function bindEvents() {
    document.querySelectorAll('[data-action="toggle-dropdown"]').forEach(function (button) {
      button.addEventListener("click", function () {
        toggleDropdown(button);
      });
    });

    document.addEventListener("click", function (event) {
      if (!event.target.closest(".dropdown")) {
        closeOpenDropdowns();
      }
    });

    document.querySelectorAll("[data-feature]").forEach(function (element) {
      element.addEventListener("click", function () {
        var featureId = element.getAttribute("data-feature");
        showFeature(featureId);
      });
    });

    document.querySelectorAll('[data-action="close-details"]').forEach(function (element) {
      element.addEventListener("click", closeDetails);
    });
  }

  document.addEventListener("DOMContentLoaded", bindEvents);
})();
