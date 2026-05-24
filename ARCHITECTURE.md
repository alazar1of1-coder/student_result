# Architecture Overview

## System Architecture

```mermaid
graph TB
    subgraph Client["Client Layer"]
        Browser["Web Browser"]
        CSS["CSS Styling<br/>(7.8%)"]
    end

    subgraph Web["Web Server Layer"]
        WebServer["Web Server<br/>(Apache/Nginx)"]
    end

    subgraph App["Application Layer"]
        PHP["PHP Application<br/>(92.2%)"]
        Router["Router/Request Handler"]
        Controller["Controllers"]
    end

    subgraph Business["Business Logic Layer"]
        Service["Business Services"]
        Validation["Data Validation"]
    end

    subgraph Data["Data Layer"]
        Database["Database<br/>(MySQL/PostgreSQL)"]
        Cache["Caching Layer<br/>(Optional)"]
    end

    Browser -->|HTTP/HTTPS| WebServer
    WebServer -->|Process Request| PHP
    PHP --> Router
    Router --> Controller
    Controller --> Service
    Service --> Validation
    Validation --> Database
    Validation --> Cache
    Database -->|Query Result| Service
    Cache -->|Cached Data| Service
    Service -->|Response| Controller
    Controller -->|HTML| PHP
    PHP -->|HTML + CSS| WebServer
    WebServer -->|Render| Browser
    CSS --> Browser

    style Browser fill:#e1f5ff
    style CSS fill:#fff9c4
    style WebServer fill:#f3e5f5
    style PHP fill:#c8e6c9
    style Router fill:#c8e6c9
    style Controller fill:#c8e6c9
    style Service fill:#ffccbc
    style Validation fill:#ffccbc
    style Database fill:#d1c4e9
    style Cache fill:#d1c4e9
```

## Component Description

### Client Layer
- **Web Browser**: Renders HTML and CSS from the server
- **CSS Styling**: Handles presentation and styling (7.8% of codebase)

### Web Server Layer
- **Web Server**: Serves HTTP requests and manages connections (Apache/Nginx)

### Application Layer
- **PHP Application**: Core application logic (92.2% of codebase)
- **Router**: Manages URL routing and request dispatch
- **Controllers**: Handles HTTP request processing and business logic orchestration

### Business Logic Layer
- **Business Services**: Core business logic and operations
- **Data Validation**: Validates input and ensures data integrity

### Data Layer
- **Database**: Persistent data storage
- **Caching Layer**: Optional caching mechanism for performance optimization

## Request Flow

1. Client sends HTTP request via browser
2. Web server receives and forwards to PHP application
3. Router analyzes the request URL
4. Controller handles the request based on route
5. Business services process the logic
6. Validation ensures data integrity
7. Database queries are executed
8. Results are returned and formatted as HTML
9. CSS styling is applied for presentation
10. Response is sent back to the browser for rendering
